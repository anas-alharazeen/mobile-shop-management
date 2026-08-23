<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'from_status',
        'to_status',
        'notes',
    ];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }
}
