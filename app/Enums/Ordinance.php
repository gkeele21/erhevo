<?php

namespace App\Enums;

enum Ordinance: string
{
    case BaptismConfirmation = 'baptism_confirmation';
    case Initiatory = 'initiatory';
    case Endowment = 'endowment';
    case Sealing = 'sealing';

    public function label(): string
    {
        return match ($this) {
            self::BaptismConfirmation => 'Baptism & Confirmation',
            self::Initiatory => 'Initiatory',
            self::Endowment => 'Endowment',
            self::Sealing => 'Sealing',
        };
    }
}
