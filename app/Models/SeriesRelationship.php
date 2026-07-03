<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SeriesRelationship extends Pivot
{
    protected $table = 'series_relationships';
    protected $guarded = [];
    public $timestamps = false;
}