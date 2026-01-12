<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use CascadeDeletes, SoftDeletes;

    protected $guarded = [];

    protected $cascadeDeletes = ['comments'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
