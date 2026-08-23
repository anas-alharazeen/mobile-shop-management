<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'company_name',
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

        return 'SUP-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
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
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ? "{$this->name} ({$this->company_name})" : $this->name;
    }

    public function getTotalPurchasesAttribute(): float
    {
        $gross = (float) $this->purchaseInvoices()->where('status', 'approved')->sum('total_amount');
        $returns = (float) $this->purchaseReturns()->where('status', 'approved')->sum('total_amount');

        return max(0, $gross - $returns);
    }

    public function getTotalPaidAttribute(): float
    {
        $payments = (float) PurchasePayment::query()
            ->whereHas('invoice', fn ($query) => $query
                ->where('supplier_id', $this->id)
                ->where('status', 'approved'))
            ->sum('amount');

        $refunds = (float) $this->purchaseReturns()
            ->where('status', 'approved')
            ->sum('amount_refunded');

        return max(0, $payments - $refunds);
    }

    public function getTotalDueAttribute(): float
    {
        return (float) $this->purchaseInvoices()
            ->where('status', 'approved')
            ->sum('remaining_amount');
    }

    public function getInvoicesCountAttribute(): int
    {
        return $this->purchaseInvoices()->where('status', 'approved')->count();
    }
}
