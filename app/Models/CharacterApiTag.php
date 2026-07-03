<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CharacterApiTag extends Pivot
{
    protected $table = 'character_api_tags';
    protected $guarded = [];
    public $timestamps = false;
}