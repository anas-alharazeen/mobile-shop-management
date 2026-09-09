<?php

namespace App\Models;

use App\Enums\FinancialTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinancialTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_account_id',
        'to_account_id',
        'amount',
        'transfer_date',
        'notes',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'datetime',
        'status' => FinancialTransferStatus::class,
        'cancelled_at' => 'datetime',
    ];

    public function fromAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }

    public function transactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'reference');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? (string) $this->status;
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return $this->status === FinancialTransferStatus::POSTED;
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'TRF-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
        } while (self::query()->where('transfer_number', $number)->exists());

        return $number;
    }
}
