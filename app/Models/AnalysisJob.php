<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisJob extends Model
{
    /** @use HasFactory<\Database\Factories\AnalysisJobFactory> */
    use HasFactory;
    protected $table = "analysis_jobs";
    protected $guarded = ['id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stations()
    {
        return $this->hasMany(StationResult::class, 'job_id', 'id');
    }

    public function simulations()
    {
        return $this->hasMany(SimulationResult::class, 'job_id', 'id');
    }
}
