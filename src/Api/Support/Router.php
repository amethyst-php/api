<?php

namespace Railken\LaraOre\Api\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class Router
{
    public static function group(\Closure $closure)
    {
        return Route::group(Config::get('ore.api.router'), function ($router) use ($closure) {
            return $closure($router);
        });
    }
}
