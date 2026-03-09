<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationResult extends Model
{
    /** @use HasFactory<\Database\Factories\SimulationResultFactory> */
    use HasFactory;
    protected $table = 'simulation_results';
    protected $guarded = ['id'];


    public function job()
    {
        return $this->belongsTo(AnalysisJob::class, 'job_id', 'id');
    }

    public function userId()
    {
        return $this->belongsTo(User::class);
    }
}
