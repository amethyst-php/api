<?php

namespace Railken\Amethyst\Tests;

use App\Controllers\FooController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Railken\Amethyst\Api\Support\Router;

abstract class BaseTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $dotenv = new \Dotenv\Dotenv(__DIR__.'/..', '.env');
        $dotenv->load();

        parent::setUp();

        Schema::dropIfExists('foo');

        Schema::create('foo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('bar_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('bar');

        Schema::create('bar', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        config(['ore.faker' => [
            'http' => [
                'admin' => [
                    'enabled'    => true,
                    'controller' => FooController::class,
                    'router'     => [
                        'prefix'      => '/foo',
                    ],
                ],
            ],
        ]]);

        Router::group('admin', ['prefix' => 'foo'], function ($router) {
            $controller = FooController::class;

            $router->get('/', ['uses' => $controller.'@index']);
            $router->post('/', ['uses' => $controller.'@create']);
            $router->put('/{id}', ['uses' => $controller.'@update']);
            $router->delete('/{id}', ['uses' => $controller.'@remove']);
            $router->get('/{id}', ['uses' => $controller.'@show']);
        });

        Route::fallback(function () {
            return response()->json(['message' => 'Not Found!'], 404);
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            \Railken\Amethyst\Providers\ApiServiceProvider::class,
        ];
    }
}
