<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Carbon\Carbon;
use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Collection|Post[] $posts
 * @property Collection|Role[] $roles
 * @property Profile $profile
 * @property Carbon|null $deleted_at
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
