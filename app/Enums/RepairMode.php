<?php

namespace App\Enums;

enum RepairMode: string
{
    case NORMAL = 'normal';
    case QUICK = 'quick';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'صيانة عادية',
            self::QUICK => 'صيانة سريعة',
        };
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            function (
                array $carry,
                self $case
            ): array {
                $carry[$case->value] =
                    $case->label();

                return $carry;
            },
            []
        );
    }
}
