<?php

namespace App\Enums;

enum Visibility: string
{
    case Private = 'private';
    case Friends = 'friends';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Friends => 'Friends Only',
            self::Public => 'Public',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Private => 'Only visible to you',
            self::Friends => 'Only visible to your friends',
            self::Public => 'Visible to everyone',
        };
    }
}
