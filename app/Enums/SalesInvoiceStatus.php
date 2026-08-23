<?php

namespace App\Enums;

enum SalesInvoiceStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::APPROVED => 'معتمدة',
            self::CANCELLED => 'ملغاة',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::APPROVED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public static function labels(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();
            return $carry;
        }, []);
    }
}
