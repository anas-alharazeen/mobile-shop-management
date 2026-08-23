<?php

namespace App\Enums;

enum WarehouseType: string
{
    case SALES = 'sales';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match($this) {
            self::SALES => 'مخزون المبيعات',
            self::MAINTENANCE => 'مخزون الصيانة',
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
