<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'system_quantity',
        'actual_quantity',
        'difference',
        'unit_cost',
        'difference_value',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
        'unit_cost' => 'decimal:2',
        'difference_value' => 'decimal:2',
    ];

    public function inventoryCount()
    {
        return $this->belongsTo(InventoryCount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getStatusAttribute()
    {
        if ($this->actual_quantity === null) {
            return ['label' => 'غير معدود', 'color' => 'gray'];
        }

        if ($this->difference === 0) {
            return ['label' => 'مطابق', 'color' => 'green'];
        }

        if ($this->difference > 0) {
            return ['label' => 'زيادة', 'color' => 'blue'];
        }

        return ['label' => 'عجز', 'color' => 'red'];
    }

    public function calculateDifference()
    {
        if ($this->actual_quantity !== null) {
            $this->difference = $this->actual_quantity - $this->system_quantity;
            $this->difference_value = $this->difference * $this->unit_cost;
            $this->save();
        }
    }
}
