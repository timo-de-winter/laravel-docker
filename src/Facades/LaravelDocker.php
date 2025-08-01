<?php

namespace TimoDeWinter\LaravelDocker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TimoDeWinter\LaravelDocker\LaravelDocker
 */
class LaravelDocker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TimoDeWinter\LaravelDocker\LaravelDocker::class;
    }
}
