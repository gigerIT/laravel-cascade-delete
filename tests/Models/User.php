<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
