<?php

namespace Tests\Unit;

use App\Services\SourceLink;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SourceLinkTest extends TestCase
{
    #[DataProvider('platformCases')]
    public function test_it_detects_the_platform(string $url, ?string $expected): void
    {
        $this->assertSame($expected, SourceLink::platformFor($url));
    }

    public static function platformCases(): array
    {
        return [
            'slides' => ['https://docs.google.com/presentation/d/abc123/edit#slide=id.p', 'Google Slides'],
            'docs' => ['https://docs.google.com/document/d/abc123/edit', 'Google Docs'],
            'sheets' => ['https://docs.google.com/spreadsheets/d/abc123/edit', 'Google Sheets'],
            'drive' => ['https://drive.google.com/file/d/abc123/view', 'Google Drive'],
            'other google' => ['https://docs.google.com/', 'Google Drive'],
            'youtube still works' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'YouTube'],
            'unknown host falls back' => ['https://example.com/a/b', 'example.com'],
        ];
    }

    #[DataProvider('embedCases')]
    public function test_it_builds_an_embed_url(?string $url, ?string $expected): void
    {
        $this->assertSame($expected, SourceLink::embedUrlFor($url));
    }

    public static function embedCases(): array
    {
        return [
            'slides edit link' => [
                'https://docs.google.com/presentation/d/1AbC-dEf_2/edit#slide=id.g123',
                'https://docs.google.com/presentation/d/1AbC-dEf_2/embed',
            ],
            'slides published link' => [
                'https://docs.google.com/presentation/d/e/2PACX-1vAbC/pub?start=false&loop=false',
                'https://docs.google.com/presentation/d/e/2PACX-1vAbC/embed',
            ],
            'google docs are not embeddable here' => ['https://docs.google.com/document/d/abc123/edit', null],
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtube short link' => ['https://youtu.be/dQw4w9WgXcQ?t=30', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'youtube shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
            'vimeo' => ['https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789'],
            'facebook has no embed' => ['https://www.facebook.com/some/post', null],
            'non-http is rejected' => ['javascript:alert(1)', null],
            'null url' => [null, null],
        ];
    }
}
