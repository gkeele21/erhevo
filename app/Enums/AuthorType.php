<?php

namespace App\Enums;

enum AuthorType: string
{
    case Self = 'self';
    case Author = 'author';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Myself',
            self::Author => 'Someone else',
        };
    }
}
