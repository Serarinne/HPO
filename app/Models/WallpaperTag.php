<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WallpaperTag extends Pivot
{
    protected $table = 'wallpaper_tag';
    protected $guarded = [];
    public $timestamps = false;
}