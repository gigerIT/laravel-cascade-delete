<?php

namespace Gigerit\LaravelCascadeDelete\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Gigerit\LaravelCascadeDelete\LaravelCascadeDelete
 */
class LaravelCascadeDelete extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Gigerit\LaravelCascadeDelete\LaravelCascadeDelete::class;
    }
}
