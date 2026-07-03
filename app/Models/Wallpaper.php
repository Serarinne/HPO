<?php

namespace App\Models;

use App\Models\Concerns\HasAllowedRating;
use App\Models\Concerns\HasStorageUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Wallpaper extends Model
{
    use HasAllowedRating, HasStorageUrl;

    protected $guarded = ['id'];

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'webp' => $this->resolveImageUrl('thumbnail', 'webp', 'image.webp'),
                'jpg' => $this->resolveImageUrl('thumbnail', 'jpg', 'image.jpg'),
            ]
        )->shouldCache();
    }

    protected function preview(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'webp' => $this->resolveImageUrl('preview', 'webp', 'image.webp'),
                'jpg' => $this->resolveImageUrl('preview', 'jpg', 'image.jpg'),
                'mp4' => $this->resolveImageUrl('preview', 'mp4', 'image.jpg'),
            ]
        )->shouldCache();
    }

    protected function original(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolveImageUrl('original', 'webp', 'image.webp')
        )->shouldCache();
    }

    protected function isVideo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => str_starts_with($attributes['file_type'] ?? '', 'video/')
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'wallpaper_artist');
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'wallpaper_character');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'wallpaper_tag');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
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

    public function isVisibleToUser(): bool
    {
        $allowed = Auth::check() ? (Auth::user()->allowed_ratings ?? ['general']) : ['general'];

        if (!is_array($allowed)) {
            $allowed = ['general'];
        }

        return in_array($this->rating, $allowed);
    }
}