<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WallpaperCharacter extends Pivot
{
    protected $table = 'wallpaper_character';
    protected $guarded = [];
    public $timestamps = false;
}