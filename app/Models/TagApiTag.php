<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TagApiTag extends Pivot
{
    protected $table = 'tag_api_tags';
    protected $guarded = [];
    public $timestamps = false;
}