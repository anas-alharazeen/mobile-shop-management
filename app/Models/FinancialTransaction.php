<?php

namespace App\Models;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_account_id',
        'type',
        'direction',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_date',
        'reference_type',
        'reference_id',
        'description',
        'notes',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'direction' => TransactionDirection::class,
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function getDirectionLabelAttribute()
    {
        return $this->direction?->label() ?? $this->direction;
    }

    public function getDirectionColorAttribute()
    {
        return $this->direction?->color() ?? 'gray';
    }

    public function getTypeLabelAttribute()
    {
        return $this->type?->label() ?? $this->type;
    }

    public function getDescriptionAttribute()
    {
        return $this->attributes['description'] ?? '';
    }
}
