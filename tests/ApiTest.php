<?php

namespace Amethyst\Tests;

use App\Fakers\FooFaker;
use Amethyst\Api\Support\Testing\TestableBaseTrait;

class ApiTest extends BaseTest
{
    use TestableBaseTrait;

    /**
     * Faker class.
     *
     * @var string
     */
    protected $faker = FooFaker::class;

    /**
     * Router group resource.
     *
     * @var string
     */
    protected $group = 'admin';

    /**
     * Route name.
     *
     * @var string
     */
    protected $route = 'admin.foo';
}
