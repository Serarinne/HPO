<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemCode extends Model
{
    protected $guarded = ['id'];
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}