<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WallpaperArtist extends Pivot
{
    protected $table = 'wallpaper_artist';
    protected $guarded = [];
    public $timestamps = false;
}