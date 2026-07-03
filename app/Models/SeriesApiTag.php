<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SeriesApiTag extends Pivot
{
    protected $table = 'series_api_tags';
    protected $guarded = [];
    public $timestamps = false;
}