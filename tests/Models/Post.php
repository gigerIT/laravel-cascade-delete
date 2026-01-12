<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Illuminate\Database\Eloquent\Collection|\Gigerit\LaravelCascadeDelete\Tests\Models\Comment[] $comments
 * @property \Carbon\Carbon|null $deleted_at
 */
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
