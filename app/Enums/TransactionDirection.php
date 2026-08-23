<?php

namespace App\Enums;

enum TransactionDirection: string
{
    case INFLOW = 'inflow';
    case OUTFLOW = 'outflow';

    public function label(): string
    {
        return match ($this) {
            self::INFLOW => 'وارد',
            self::OUTFLOW => 'صادر',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFLOW => 'green',
            self::OUTFLOW => 'red',
        };
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            function (array $labels, self $direction): array {
                $labels[$direction->value] = $direction->label();

                return $labels;
            },
            []
        );
    }
}
