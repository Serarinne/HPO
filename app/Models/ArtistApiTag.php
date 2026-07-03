<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ArtistApiTag extends Pivot
{
    protected $table = 'artist_api_tags';
    protected $guarded = [];
    public $timestamps = false;
}