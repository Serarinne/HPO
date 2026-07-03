<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasAllowedRating
{
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('slug')
            ->whereNotNull('seo_title')
            ->where('seo_title', '!=', '');
    }

    public function scopeAllowedRating(Builder $query): Builder
    {
        if (Auth::check()) {
            $ratings = Auth::user()->allowed_ratings;

            if (!is_array($ratings) || empty($ratings)) {
                $ratings = ['general'];
            }

            return $query->whereIn('rating', $ratings);
        }

        return $query->where('rating', 'general');
    }
}