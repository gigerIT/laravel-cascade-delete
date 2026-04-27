<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Carbon|null $deleted_at
 */
class Profile extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
