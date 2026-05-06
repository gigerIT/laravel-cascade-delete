<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Carbon|null $deleted_at
 */
class Comment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
