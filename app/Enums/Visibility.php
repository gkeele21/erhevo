<?php

namespace App\Enums;

enum Visibility: string
{
    case Private = 'private';
    case Friends = 'friends';
    case Custom = 'custom';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Private',
            self::Friends => 'All Friends',
            self::Custom => 'Specific Friends',
            self::Public => 'Public',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Private => 'Only visible to you',
            self::Friends => 'Visible to all of your friends',
            self::Custom => 'Only the friends you pick',
            self::Public => 'Visible to everyone',
        };
    }
}
