<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CharacterSeries extends Pivot
{
    protected $table = 'character_series';
    protected $guarded = [];
    public $timestamps = false;
}