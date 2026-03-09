<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroundTruth extends Model
{
    /** @use HasFactory<\Database\Factories\GroundTruthFactory> */
    use HasFactory;
    protected $table = 'ground_truths';
    protected $guarded = ['id'];
    public $timestamps = false;


    public function station()
    {
        return $this->belongsTo(StationResult::class, 'station_id');
    }

    public function inputBy()
    {
        return $this->belongsTo(User::class);
    }

    public function rawId()
    {
        return $this->belongsTo(RawSegment::class);
    }

}
