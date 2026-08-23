<?php

namespace App\Enums;

enum CategoryType: string
{
    case PHONE = 'phone';
    case TABLET = 'tablet';
    case ACCESSORY = 'accessory';
    case SPARE_PART = 'spare_part';
    case MAINTENANCE_TOOL = 'maintenance_tool';
    case MAINTENANCE_MATERIAL = 'maintenance_material';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::PHONE => 'هواتف',
            self::TABLET => 'أجهزة لوحية',
            self::ACCESSORY => 'إكسسوارات',
            self::SPARE_PART => 'قطع غيار',
            self::MAINTENANCE_TOOL => 'أدوات صيانة',
            self::MAINTENANCE_MATERIAL => 'مواد صيانة',
            self::OTHER => 'أخرى',
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
