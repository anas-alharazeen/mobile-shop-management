<?php

namespace App\Enums;

enum AttachmentStage: string
{
    case RECEIVED = 'received';
    case BEFORE_REPAIR = 'before_repair';
    case AFTER_REPAIR = 'after_repair';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::RECEIVED => 'عند الاستلام',
            self::BEFORE_REPAIR => 'قبل الإصلاح',
            self::AFTER_REPAIR => 'بعد الإصلاح',
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
}
