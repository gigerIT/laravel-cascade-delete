<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $url
 * @property string $imageable_type
 * @property int $imageable_id
 */
class Image extends Model
{
    protected $guarded = [];

    public function imageable()
    {
        return $this->morphTo();
    }
}
