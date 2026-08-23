<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAccountClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_account_id',
        'closing_date',
        'opening_balance',
        'total_inflows',
        'total_outflows',
        'expected_balance',
        'actual_balance',
        'difference',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'total_inflows' => 'decimal:2',
        'total_outflows' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function getDifferenceStatusAttribute()
    {
        if ($this->difference == 0) {
            return ['label' => 'مطابق', 'color' => 'green'];
        }
        if ($this->difference > 0) {
            return ['label' => 'زيادة', 'color' => 'blue'];
        }
        return ['label' => 'عجز', 'color' => 'red'];
    }
}
