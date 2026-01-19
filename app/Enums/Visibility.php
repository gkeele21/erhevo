<?php

namespace App\Enums;

enum Visibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Friends = 'friends';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private',
            self::Friends => 'Friends Only',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Visible to everyone',
            self::Private => 'Only visible to you',
            self::Friends => 'Only visible to your friends',
        };
    }
}
