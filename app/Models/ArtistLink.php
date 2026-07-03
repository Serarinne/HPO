<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistLink extends Model
{
    protected $guarded = ['id'];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}