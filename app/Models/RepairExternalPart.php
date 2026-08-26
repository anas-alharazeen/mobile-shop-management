<?php

namespace App\Models;

use App\Enums\RepairExternalPartStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairExternalPart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'repair_order_id',
        'supplier_id',
        'financial_account_id',
        'financial_transaction_id',
        'part_name',
        'quantity',
        'status',
        'purchase_from',
        'supplier_phone',
        'purchase_reference',
        'unit_purchase_price',
        'total_purchase_cost',
        'customer_unit_price',
        'total_customer_price',
        'purchased_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'status' => RepairExternalPartStatus::class,
        'unit_purchase_price' => 'decimal:2',
        'total_purchase_cost' => 'decimal:2',
        'customer_unit_price' => 'decimal:2',
        'total_customer_price' => 'decimal:2',
        'purchased_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === RepairExternalPartStatus::DRAFT;
    }

    public function getIsPurchasedAttribute(): bool
    {
        return $this->status === RepairExternalPartStatus::PURCHASED;
    }
}
