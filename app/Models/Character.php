<?php

namespace App\Models;

use App\Models\Concerns\HasAllowedRating;
use App\Models\Concerns\HasStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Character extends Model
{
    use HasAllowedRating, HasStorageUrl, SoftDeletes;

    protected $guarded = ['id'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'webp' => $this->resolveImageUrl('image', 'webp', 'person.webp'),
                'jpg' => $this->resolveImageUrl('image', 'jpg', 'person.jpg'),
            ]
        )->shouldCache();
    }

    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class, 'character_series');
    }

    public function wallpapers(): BelongsToMany
    {
        return $this->belongsToMany(Wallpaper::class, 'wallpaper_character');
    }

    public function wallpaperCount(): HasOne
    {
        return $this->hasOne(DataCount::class, 'data_id', 'id')
            ->where('type', 'character')
            ->withDefault(['total' => 0]);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'character_relationships',
            'parent_id',
            'child_id'
        )->withPivot('relation_type');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'character_relationships',
            'child_id',
            'parent_id'
        )->withPivot('relation_type');
    }

    protected function parent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->relationLoaded('parents') ? $this->parents->first() : null
        );
    }

    public function scopeInternalPopular($query)
    {
        return $query->hasWallpapers()->orderByDesc('dc.total');
    }

    public function scopeHasWallpapers($query)
    {
        return $query->join('data_counts as dc', fn ($join) =>
            $join->on('characters.id', '=', 'dc.data_id')
                ->where('dc.type', '=', 'character')
        )
        ->where('dc.total', '>', 0)
        ->select(
            'characters.id',
            'characters.name',
            'characters.slug',
            'characters.image',
            'dc.total as wallpapers_count'
        );
    }

    public function scopeSearch($query, string $term)
    {
        if (strlen($term) <= 3) {
            return $query->where('keywords', 'LIKE', "%{$term}%");
        }

        $formattedTerm = implode(' ', array_map(
            fn ($word) => strlen($word) < 3 ? "{$word}*" : "+{$word}*",
            array_filter(explode(' ', $term))
        ));

        return $query->whereFullText('keywords', $formattedTerm, ['mode' => 'boolean']);
    }

    public function relatedCharacters(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->parents->merge($this->children)
        );
    }

    public function apiTags(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CharacterApiTag::class, 'character_id');
    }
}