<?php

namespace App\Enums;

enum CfmContentType: string
{
    case Video = 'video';
    case Podcast = 'podcast';
    case Blog = 'blog';
    case Pdf = 'pdf';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Podcast => 'Podcast',
            self::Blog => 'Blog Post',
            self::Pdf => 'PDF Document',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Video => 'play-circle',
            self::Podcast => 'microphone',
            self::Blog => 'document-text',
            self::Pdf => 'document',
            self::Other => 'link',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Video => 'YouTube, Vimeo, or other video content',
            self::Podcast => 'Audio podcast episodes',
            self::Blog => 'Written articles and blog posts',
            self::Pdf => 'Downloadable PDF documents',
            self::Other => 'Other external resources',
        };
    }
}
