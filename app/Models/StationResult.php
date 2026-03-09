<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationResult extends Model
{
    /** @use HasFactory<\Database\Factories\StationResultFactory> */
    use HasFactory;

    use HasFactory;
    protected $table = 'station_results';
    protected $guarded = ['id'];


    public function job()
    {
        return $this->belongsTo(AnalysisJob::class, 'job_id', 'id');
    }

    public function workElements()
    {
        return $this->hasMany(WorkElement::class, 'station_id', 'id');
    }

    public function rawSegments()
    {
        return $this->hasMany(RawSegment::class, 'station_id', 'id');
    }

    public function groundTruths()
    {
        return $this->hasMany(GroundTruth::class, 'station_id', 'id');
    }

    public function temporalIoUResults()
    {
        return $this->hasMany(TemporalIoUResult::class, 'station_id', 'id');
    }

}
