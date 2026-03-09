<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporalIoUResult extends Model
{
    /** @use HasFactory<\Database\Factories\TemporalIoUResultFactory> */
    use HasFactory;
    protected $table = 'temporal_iou_results';
    protected $guarded = ['id'];
    public $timestamps = false;



    public function station()
    {
        return $this->belongsTo(StationResult::class, 'station_id');
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class);
    }
}
