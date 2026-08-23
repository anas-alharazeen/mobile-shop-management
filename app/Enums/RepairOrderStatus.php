<?php

namespace App\Enums;

enum RepairOrderStatus: string
{
    case RECEIVED = 'received';
    case IN_PROGRESS = 'in_progress';
    case READY = 'ready';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::RECEIVED => 'مستلم',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::READY => 'جاهز للاستلام',
            self::DELIVERED => 'تم التسليم',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::RECEIVED => 'blue',
            self::IN_PROGRESS => 'orange',
            self::READY => 'green',
            self::DELIVERED => 'gray',
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
