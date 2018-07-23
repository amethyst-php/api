<?php

namespace Railken\LaraOre\Api\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class Router
{
    public static function group(string $container, array $config, \Closure $closure)
    {
        return Route::group(Config::get('ore.api.router'), function ($router) use ($config, $closure, $container) {
            return Route::group(array_merge(Config::get('ore.api.http.'.$container.'.router'), $config), $closure);
        });
    }
}
