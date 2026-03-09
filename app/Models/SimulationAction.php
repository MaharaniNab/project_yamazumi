<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationAction extends Model
{
    /** @use HasFactory<\Database\Factories\SimulationActionFactory> */
    use HasFactory;
    protected $table = 'simulation_actions';
    protected $guarded = ['id'];
    public $timestamps = false;

    
    public function simulation()
    {
        return $this->belongsTo(SimulationResult::class, 'simulation_id', 'id');
    }
}
