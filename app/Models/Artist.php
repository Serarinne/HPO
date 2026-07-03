<?php

namespace App\Models;

use App\Models\Concerns\HasStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Artist extends Model
{
    use HasStorageUrl;

    protected $guarded = ['id'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => [
                'webp' => $this->resolveImageUrl('image', 'webp', 'person.webp'),
                'jpg' => $this->resolveImageUrl('image', 'jpg', 'person.jpg'),
            ]
        );
    }

    protected function wallpapersCount(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (array_key_exists('wallpapers_count', $attributes)) {
                    return $attributes['wallpapers_count'];
                }

                if (array_key_exists('sorted_count', $attributes)) {
                    return $attributes['sorted_count'];
                }

                if ($this->relationLoaded('dataCount') && $this->dataCount) {
                    return $this->dataCount->total;
                }

                return 0;
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(ArtistLink::class);
    }

    public function wallpapers(): BelongsToMany
    {
        return $this->belongsToMany(Wallpaper::class, 'wallpaper_artist');
    }

    public function dataCount(): HasOne
    {
        return $this->hasOne(DataCount::class, 'data_id')->where('type', 'artist');
    }

    public function scopeSearch($query, $term)
    {
        if (strlen($term) <= 3) {
            return $query->where('name', 'LIKE', "%{$term}%");
        }

        $words = array_filter(explode(' ', $term));
        $formattedWords = array_map(function ($word) {
            return strlen($word) < 3 ? $word . '*' : '+' . $word . '*';
        }, $words);

        $formattedTerm = implode(' ', $formattedWords);

        return $query->whereFullText(['keywords', 'name'], $formattedTerm, ['mode' => 'boolean']);
    }

    public function scopeIndexList($query)
    {
        return $query->join("data_counts as dc", function ($join) {
            $join->on('artists.id', '=', 'dc.data_id')
                ->where('dc.type', '=', 'artist');
        })
        ->where('dc.total', '>', 0)
        ->orderByDesc('dc.total')
        ->select(
            'artists.id',
            'artists.name',
            'artists.slug',
            'artists.image',
            'artists.debug',
            'dc.total as wallpapers_count'
        );
    }

    public function apiTags()
    {
        return $this->hasMany(ArtistApiTag::class, 'artist_id', 'id');
    }
}