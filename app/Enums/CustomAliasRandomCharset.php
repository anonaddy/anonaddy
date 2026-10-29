<?php

namespace App\Enums;

enum CustomAliasRandomCharset: string
{
    case Alphanumeric = 'alphanumeric';
    case Letters = 'letters';
    case Numbers = 'numbers';

    public function alphabet(): string
    {
        return match ($this) {
            self::Letters => 'abcdefghijklmnopqrstuvwxyz',
            self::Numbers => '0123456789',
            self::Alphanumeric => '0123456789abcdefghijklmnopqrstuvwxyz',
        };
    }
}
