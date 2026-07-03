<?php

namespace App\Models;

use App\Models\Concerns\HasAllowedRating;
use App\Models\Concerns\HasStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tag extends Model
{
    use HasAllowedRating, HasStorageUrl;

    protected $guarded = ['id'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'webp' => $this->resolveImageUrl('image', 'webp', 'tag.webp'),
                'jpg' => $this->resolveImageUrl('image', 'jpg', 'tag.jpg'),
            ]
        )->shouldCache();
    }

    public function wallpapers(): BelongsToMany
    {
        return $this->belongsToMany(Wallpaper::class, 'wallpaper_tag');
    }

    public function dataCount(): HasOne
    {
        return $this->hasOne(DataCount::class, 'data_id', 'id')
            ->where('type', 'tag')
            ->withDefault(['total' => 0]);
    }

    public function scopeTrending($query, $limit = 8)
    {
        return $query->join('data_counts as dc', fn ($join) =>
                $join->on('tags.id', '=', 'dc.data_id')
                    ->where('dc.type', '=', 'tag')
            )
            ->addSelect('dc.total as sorted_count')
            ->orderByDesc('dc.total')
            ->take($limit);
    }

    protected function wallpapersCount(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $attributes['wallpapers_count']
                    ?? $attributes['sorted_count']
                    ?? ($this->relationLoaded('dataCount') && $this->dataCount ? $this->dataCount->total : 0);
            }
        );
    }

    public function scopeIndexList($query)
    {
        return $query->join('data_counts as dc', fn ($join) =>
                $join->on('tags.id', '=', 'dc.data_id')
                    ->where('dc.type', '=', 'tag')
            )
            ->where('dc.total', '>', 0)
            ->orderByDesc('dc.total')
            ->select(
                'tags.id',
                'tags.name',
                'tags.slug',
                'dc.total as wallpapers_count'
            );
    }

    public function scopeSearch($query, string $term)
    {
        if (strlen($term) <= 3) {
            return $query->where(fn ($q) =>
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('keywords', 'LIKE', "%{$term}%")
            );
        }

        $formattedTerm = implode(' ', array_map(
            fn ($word) => strlen($word) < 3 ? "{$word}*" : "+{$word}*",
            array_filter(explode(' ', $term))
        ));

        return $query->whereFullText('keywords', $formattedTerm, ['mode' => 'boolean']);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function apiTags()
    {
        return $this->hasMany(TagApiTag::class, 'tag_id', 'id');
    }
}