<?php

namespace Railken\LaraOre\Api\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class Router
{
    public static function group(array $config, \Closure $closure)
    {
        return Route::group(Config::get('ore.api.router'), function ($router) use ($config, $closure) {
            return Route::group($config, $closure);
        });
    }
}
