<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Download the audio track of a social-media video (Instagram reel, TikTok,
 * YouTube, ...) so it can be transcribed. Wraps yt-dlp + ffmpeg, which must
 * be installed on the host (`brew install yt-dlp ffmpeg`).
 *
 * Platforms rate-limit and login-wall aggressively, so callers must treat
 * failures as expected and fall back to letting the user paste the text.
 */
class SocialVideo
{
    /**
     * Longest video we'll download for transcription, in seconds.
     */
    private const MAX_DURATION = 1200;

    /**
     * Fetch a video's metadata and extract its audio to a temp mp3.
     *
     * @return array{
     *     audio_path: string,
     *     author_name: ?string,
     *     username: ?string,
     *     caption: ?string,
     *     title: ?string,
     *     date_given: ?string,
     *     duration: ?int,
     * }
     *
     * @throws \RuntimeException when the video can't be fetched
     */
    public function fetchAudio(string $url): array
    {
        SourceLink::assertSafe($url);

        $binary = $this->binaryPath('yt-dlp');
        $workDir = sys_get_temp_dir() . '/erhevo-transcribe-' . Str::random(12);
        File::makeDirectory($workDir);

        try {
            // Single yt-dlp call: downloads, extracts mp3 (via ffmpeg), and
            // prints the video's metadata JSON to stdout.
            $result = Process::timeout(240)
                ->env(['PATH' => $this->toolPath()])
                ->run([
                    $binary,
                    '--no-playlist',
                    '--no-warnings',
                    // "<?": pass when the source reports no duration (Instagram
                    // reels don't) — the max-filesize cap still applies.
                    '--match-filter', 'duration <? ' . self::MAX_DURATION,
                    '--max-filesize', '500M',
                    '-x', '--audio-format', 'mp3', '--audio-quality', '64K',
                    '--print-json',
                    '-o', "{$workDir}/audio.%(ext)s",
                    $url,
                ]);

            if ($result->failed()) {
                throw new \RuntimeException($this->friendlyError($result->errorOutput()));
            }

            $meta = json_decode($result->output(), true) ?: [];

            // A rejected --match-filter (video too long) still exits 0, but
            // prints no metadata and downloads nothing.
            if ($meta === []) {
                throw new \RuntimeException('That video could not be downloaded — it may be private, unavailable, or too long to transcribe.');
            }

            $audioPath = "{$workDir}/audio.mp3";

            if (! is_file($audioPath) || filesize($audioPath) === 0) {
                throw new \RuntimeException('The post has no audio track to transcribe.');
            }

            return [
                'audio_path' => $audioPath,
                'author_name' => $meta['uploader'] ?? $meta['channel'] ?? $meta['uploader_id'] ?? null,
                // Instagram puts the handle in `channel`; `uploader_id` is a numeric account id there.
                'username' => $meta['channel'] ?? $meta['uploader_id'] ?? null,
                'caption' => $meta['description'] ?? null,
                'title' => $meta['title'] ?? null,
                'date_given' => isset($meta['upload_date'])
                    ? substr($meta['upload_date'], 0, 4) . '-' . substr($meta['upload_date'], 4, 2) . '-' . substr($meta['upload_date'], 6, 2)
                    : null,
                'duration' => isset($meta['duration']) ? (int) $meta['duration'] : null,
            ];
        } catch (\Throwable $e) {
            File::deleteDirectory($workDir);
            throw $e;
        }
    }

    /**
     * Remove the temp directory holding a previously fetched audio file.
     */
    public function cleanup(string $audioPath): void
    {
        $dir = dirname($audioPath);
        if (str_starts_with($dir, sys_get_temp_dir() . '/erhevo-transcribe-')) {
            File::deleteDirectory($dir);
        }
    }

    /**
     * PHP-FPM's PATH usually lacks the Homebrew bin dirs, so search there too.
     */
    private function binaryPath(string $name): string
    {
        $configured = config("services.{$name}.path");
        if ($configured && is_executable($configured)) {
            return $configured;
        }

        foreach (['/opt/homebrew/bin', '/usr/local/bin', '/usr/bin'] as $dir) {
            if (is_executable("{$dir}/{$name}")) {
                return "{$dir}/{$name}";
            }
        }

        throw new \RuntimeException(
            "{$name} is not installed on the server — video transcription is unavailable."
        );
    }

    /**
     * PATH for the yt-dlp subprocess, so it can find ffmpeg.
     */
    private function toolPath(): string
    {
        return implode(':', ['/opt/homebrew/bin', '/usr/local/bin', '/usr/bin', '/bin']);
    }

    /**
     * Collapse yt-dlp's stderr into something a user can act on.
     */
    private function friendlyError(string $stderr): string
    {
        $stderr = strtolower($stderr);

        return match (true) {
            str_contains($stderr, 'login') || str_contains($stderr, 'rate-limit') || str_contains($stderr, 'not available')
                => 'That post could not be downloaded — it may be private, deleted, or the platform is blocking automated access right now.',
            str_contains($stderr, 'unsupported url')
                => 'That link is not a video post that can be transcribed.',
            str_contains($stderr, 'duration')
                => 'That video is too long to transcribe.',
            default => 'The video could not be downloaded from that link.',
        };
    }
}
