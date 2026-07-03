<?php

namespace App\Models;

use App\Models\Concerns\HasAllowedRating;
use App\Models\Concerns\HasStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Series extends Model
{
    use HasAllowedRating, HasStorageUrl;

    protected $guarded = ['id'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'webp' => $this->resolveImageUrl('image', 'webp', 'image.webp'),
                'jpg' => $this->resolveImageUrl('image', 'jpg', 'image.jpg'),
            ]
        )->shouldCache();
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_series');
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

    public function children()
    {
        return $this->belongsToMany(
            Series::class,
            'series_relationships',
            'parent_id',
            'child_id'
        )->withPivot('relation_type');
    }

    public function apiTags()
    {
        return $this->hasMany(SeriesApiTag::class, 'series_id', 'id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Series::class, 'series_relationships', 'child_id', 'parent_id')
            ->withPivot('relation_type');
    }
}