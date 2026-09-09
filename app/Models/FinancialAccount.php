<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinancialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'current_balance',
        'description',
        'logo_path',
        'is_active',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(FinancialTransfer::class, 'from_account_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(FinancialTransfer::class, 'to_account_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function closings()
    {
        return $this->hasMany(DailyAccountClosing::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTypeLabelAttribute()
    {
        return $this->type?->label() ?? $this->type;
    }


    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->current_balance, 2) . ' شيكل';
    }

    public function getTodayInflowsAttribute()
    {
        return $this->transactions()
            ->where('direction', 'inflow')
            ->whereDate('transaction_date', today())
            ->sum('amount');
    }

    public function getTodayOutflowsAttribute()
    {
        return $this->transactions()
            ->where('direction', 'outflow')
            ->whereDate('transaction_date', today())
            ->sum('amount');
    }

    public function getBalanceBeforeDate($date): float
    {
        $movementBalance = (float) $this->transactions()
            ->whereDate('transaction_date', '<', $date)
            ->sum(DB::raw("CASE WHEN direction = 'inflow' THEN amount ELSE -amount END"));

        return (float) $this->opening_balance + $movementBalance;
    }
}
