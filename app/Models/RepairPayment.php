<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'amount',
        'financial_account_id',
        'payment_method',
        'bank_or_app_name',
        'transaction_reference',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
        'paid_at' => 'datetime',
    ];

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

public function repairOrder()
{
    return $this
        ->belongsTo(RepairOrder::class)
        ->withTrashed();
}
}
