<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function generateCode(): string
    {
        $last = self::withTrashed()->latest('id')->first();
        $number = $last ? ((int) substr($last->code, 4)) + 1 : 1;

        return 'CUS-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($nested) use ($search): void {
            $nested->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function repairOrders()
    {
        return $this->hasMany(RepairOrder::class);
    }

    public function getInitialsAttribute(): string
    {
        return mb_substr($this->name, 0, 1, 'UTF-8');
    }

    public function getTotalSalesAttribute(): float
    {
        $gross = (float) $this->salesInvoices()->where('status', 'approved')->sum('total_amount');
        $returns = (float) $this->salesReturns()->where('status', 'approved')->sum('total_amount');

        return max(0, $gross - $returns);
    }

    public function getTotalPaidAttribute(): float
    {
        $payments = (float) SalesPayment::query()
            ->whereHas('invoice', fn ($query) => $query
                ->where('customer_id', $this->id)
                ->where('status', 'approved'))
            ->where('payment_method', '!=', PaymentMethod::EXCHANGE_CREDIT->value)
            ->sum('amount');

        $refunds = (float) $this->salesReturns()
            ->where('status', 'approved')
            ->sum('amount_refunded');

        return max(0, $payments - $refunds);
    }

    public function getTotalDueAttribute(): float
    {
        return (float) $this->salesInvoices()
            ->where('status', 'approved')
            ->sum('remaining_amount');
    }

    public function getSalesCountAttribute(): int
    {
        return $this->salesInvoices()->where('status', 'approved')->count();
    }

    public function getRepairOrdersCountAttribute(): int
    {
        return $this->repairOrders()->count();
    }

    public function getActiveRepairOrdersCountAttribute(): int
    {
        return $this->repairOrders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();
    }

    public function getReadyRepairOrdersCountAttribute(): int
    {
        return $this->repairOrders()->where('status', 'ready')->count();
    }

    public function getTotalRepairValueAttribute(): float
    {
        return (float) $this->repairOrders()
            ->where('status', 'delivered')
            ->sum('total_amount');
    }

    public function getTotalRepairPaidAttribute(): float
    {
        return (float) $this->repairOrders()
            ->where('status', 'delivered')
            ->sum('paid_amount');
    }

    public function getTotalRepairDueAttribute(): float
    {
        return (float) $this->repairOrders()
            ->where('status', 'delivered')
            ->sum('remaining_amount');
    }
}
