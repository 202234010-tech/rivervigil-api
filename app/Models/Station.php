<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'river_basin',
        'location',
        'current_level_cm',
        'flow_rate_m3s',
        'alert_status',
        'thresholds_cm',
    ];

    protected $casts = [
        'thresholds_cm' => 'array',
        'current_level_cm' => 'float',
        'flow_rate_m3s' => 'float',
    ];
}
