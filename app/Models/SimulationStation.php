<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationStation extends Model
{
    /** @use HasFactory<\Database\Factories\SimulationStationFactory> */
    use HasFactory;
    protected $table = 'simulation_stations';
    protected $guarded = ['id'];
    public $timestamps = false;

    
    public function simulation()
    {
        return $this->belongsTo(SimulationResult::class, 'simulation_id', 'id');
    }
}
