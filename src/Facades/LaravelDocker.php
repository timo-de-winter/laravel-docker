<?php

namespace TimoDeWinter\LaravelDocker\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelDocker extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \TimoDeWinter\LaravelDocker\LaravelDocker::class;
    }
}
