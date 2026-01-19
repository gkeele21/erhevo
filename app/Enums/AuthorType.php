<?php

namespace App\Enums;

enum AuthorType: string
{
    case Self = 'self';
    case Text = 'text';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Myself',
            self::Text => 'Custom Author',
            self::User => 'Another User',
        };
    }
}
