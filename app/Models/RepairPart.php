<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'product_id',
        'warehouse_id',
        'product_name',
        'quantity',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_price',
        'is_committed',
        'committed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_committed' => 'boolean',
        'committed_at' => 'datetime',
    ];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
