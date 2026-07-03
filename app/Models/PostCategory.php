<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostCategory extends Model
{
    protected $guarded = ['id'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_category', 'category_id', 'post_id');
    }
}