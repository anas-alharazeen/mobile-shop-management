<?php

namespace App\Models;

use App\Enums\AttachmentStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_order_id',
        'file_path',
        'file_type',
        'stage',
        'notes',
    ];

    protected $casts = [
        'stage' => AttachmentStage::class,
    ];

    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }
}
