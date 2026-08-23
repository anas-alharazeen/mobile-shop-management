<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::POSTED => 'معتمد',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::POSTED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
