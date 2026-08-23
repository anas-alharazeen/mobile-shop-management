<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';

    public function label(): string
    {
        return match($this) {
            self::UNPAID => 'غير مدفوعة',
            self::PARTIALLY_PAID => 'مدفوعة جزئياً',
            self::PAID => 'مدفوعة بالكامل',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::UNPAID => 'red',
            self::PARTIALLY_PAID => 'orange',
            self::PAID => 'green',
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
