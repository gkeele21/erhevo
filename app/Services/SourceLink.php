<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Source links on posts: detect the platform behind a URL and make a
 * best-effort attempt to read the linked page's text (Open Graph metadata).
 *
 * Many platforms (Facebook especially) put posts behind a login wall, so
 * fetching can legitimately fail — callers must treat the text as optional
 * and fall back to letting the user paste it.
 */
class SourceLink
{
    private const PLATFORMS = [
        'facebook.com' => 'Facebook',
        'fb.com' => 'Facebook',
        'fb.watch' => 'Facebook',
        'instagram.com' => 'Instagram',
        'twitter.com' => 'X (Twitter)',
        'x.com' => 'X (Twitter)',
        'threads.net' => 'Threads',
        'youtube.com' => 'YouTube',
        'youtu.be' => 'YouTube',
        'tiktok.com' => 'TikTok',
        'linkedin.com' => 'LinkedIn',
        'pinterest.com' => 'Pinterest',
        'reddit.com' => 'Reddit',
        'medium.com' => 'Medium',
        'substack.com' => 'Substack',
    ];

    public static function platformFor(?string $url): ?string
    {
        $host = $url ? strtolower((string) parse_url($url, PHP_URL_HOST)) : null;
        if (! $host) {
            return null;
        }

        foreach (self::PLATFORMS as $domain => $label) {
            if ($host === $domain || str_ends_with($host, ".{$domain}")) {
                return $label;
            }
        }

        // Fall back to the bare domain so the source still reads sensibly.
        return Str::of($host)->replaceStart('www.', '')->toString();
    }

    /**
     * Fetch the page and pull the most post-like text available.
     *
     * @return array{title: ?string, text: ?string}
     *
     * @throws \RuntimeException when the URL is unsafe or yields no usable text
     */
    public function fetchText(string $url): array
    {
        $this->assertSafe($url);

        $response = Http::timeout(8)
            ->withHeaders([
                // A browsery UA gets us the public/OG version of more pages.
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($url);

        if (! $response->successful() || ! str_contains((string) $response->header('Content-Type'), 'html')) {
            throw new \RuntimeException('The page could not be read.');
        }

        $meta = $this->parseMeta($response->body());

        // Login walls render a generic page: og:description missing or boilerplate.
        $text = $meta['description'] ?? null;
        if (! $text || strlen($text) < 20 || str_contains(strtolower($text), 'log in')) {
            throw new \RuntimeException('The page could not be read.');
        }

        return ['title' => $meta['title'] ?? null, 'text' => $text];
    }

    /**
     * Refuse URLs that could reach internal services (SSRF).
     */
    private function assertSafe(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https']) || $host === '') {
            throw new \RuntimeException('Only regular web links are supported.');
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP)
            && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \RuntimeException('Only regular web links are supported.');
        }
    }

    /**
     * @return array{title: ?string, description: ?string}
     */
    private function parseMeta(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

        $meta = ['title' => null, 'description' => null];

        foreach ($doc->getElementsByTagName('meta') as $tag) {
            $key = $tag->getAttribute('property') ?: $tag->getAttribute('name');
            $content = trim($tag->getAttribute('content'));
            if ($content === '') {
                continue;
            }

            if (in_array($key, ['og:description', 'twitter:description', 'description'])) {
                // Prefer og:description; only fill from the others when empty.
                if ($key === 'og:description' || ! $meta['description']) {
                    $meta['description'] = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
                }
            }

            if ($key === 'og:title' && ! $meta['title']) {
                $meta['title'] = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
            }
        }

        if (! $meta['title']) {
            $titleTag = $doc->getElementsByTagName('title')->item(0);
            $meta['title'] = $titleTag ? trim($titleTag->textContent) : null;
        }

        return $meta;
    }
}
