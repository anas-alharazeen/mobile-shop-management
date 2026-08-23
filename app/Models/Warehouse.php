<?php

namespace App\Models;

use App\Enums\WarehouseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => WarehouseType::class,
    ];

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
