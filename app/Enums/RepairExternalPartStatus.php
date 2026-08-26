<?php

namespace App\Enums;

enum RepairExternalPartStatus: string
{
    case DRAFT = 'draft';
    case PURCHASED = 'purchased';
    case RETURNED = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'بانتظار الشراء',
            self::PURCHASED => 'تم الشراء',
            self::RETURNED => 'تم إرجاعها',
        };
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            function (array $carry, self $case): array {
                $carry[$case->value] = $case->label();
                return $carry;
            },
            []
        );
    }
}
