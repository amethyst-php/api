<?php

namespace Railken\LaraOre\Api\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class Router
{
    public static function group(string $container, array $config, \Closure $closure)
    {
        return Route::group(Config::get('ore.api.http.'.$container.'.router', []), function ($router) use ($config, $closure) {
            return Route::group($config, $closure);
        });
    }
}
