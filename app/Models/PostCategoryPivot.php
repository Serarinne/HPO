<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PostCategoryPivot extends Pivot
{
    protected $table = 'post_category';
    protected $guarded = [];
    public $timestamps = false;
}