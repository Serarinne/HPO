<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'password' => 'hashed',
            'allowed_ratings' => 'array',
        ];
    }

    public function links(): HasMany
    {
        return $this->hasMany(UserLink::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function wallpapers(): HasMany
    {
        return $this->hasMany(Wallpaper::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}