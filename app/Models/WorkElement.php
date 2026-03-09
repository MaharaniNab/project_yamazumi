<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkElement extends Model
{
    /** @use HasFactory<\Database\Factories\WorkElementFactory> */
    use HasFactory;

    protected $table = 'work_elements';
    protected $guarded = ['id'];
    public $timestamps = false;



    public function station()
    {
        return $this->belongsTo(StationResult::class, 'station_id', 'id');
    }
}
