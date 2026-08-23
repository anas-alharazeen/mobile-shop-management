<?php

namespace App\Enums;

enum InventoryCountStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::COMPLETED => 'معتمد',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
