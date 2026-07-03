<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FetchTask extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'last_run_at' => 'datetime',
        'last_source_id' => 'integer',
        'last_post_date' => 'datetime',
    ];
}