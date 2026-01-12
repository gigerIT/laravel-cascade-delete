<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Illuminate\Database\Eloquent\Collection|\Gigerit\LaravelCascadeDelete\Tests\Models\Post[] $posts
 * @property \Illuminate\Database\Eloquent\Collection|\Gigerit\LaravelCascadeDelete\Tests\Models\Role[] $roles
 * @property \Gigerit\LaravelCascadeDelete\Tests\Models\Profile $profile
 * @property \Carbon\Carbon|null $deleted_at
 */
class User extends Model
{
    use CascadeDeletes, SoftDeletes;

    protected $guarded = [];

    protected $cascadeDeletes = ['posts', 'roles', 'profile'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
