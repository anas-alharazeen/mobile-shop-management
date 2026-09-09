<?php

namespace App\Enums;

enum FinancialTransferStatus: string
{
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::POSTED => 'مكتمل',
            self::CANCELLED => 'ملغي',
        };
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            function (array $labels, self $status): array {
                $labels[$status->value] = $status->label();

                return $labels;
            },
            []
        );
    }
}
