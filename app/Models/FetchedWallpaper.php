<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // Pastikan extends Model

class FetchedWallpaper extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tags' => 'array',
        'characters' => 'array',
        'artists' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected function isVideo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => str_starts_with($attributes['file_type'] ?? '', 'mp4')
        );
    }
}