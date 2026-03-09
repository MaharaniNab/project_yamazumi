<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawSegment extends Model
{
    /** @use HasFactory<\Database\Factories\RawSegmentFactory> */
    use HasFactory;
    protected $table = 'raw_segments';
    protected $guarded = ['id'];
    public $timestamps = false;


    public function station()
    {
        return $this->belongsTo(StationResult::class, 'station_id', 'id');
    }

}
