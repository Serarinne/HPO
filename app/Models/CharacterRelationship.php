<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CharacterRelationship extends Pivot
{
    protected $table = 'character_relationships';
    protected $guarded = [];
    public $timestamps = false;
}