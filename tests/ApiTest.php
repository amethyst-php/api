<?php

namespace Railken\Amethyst\Tests;

use Foo;
use Railken\Amethyst\Api\Support\Testing\TestableBaseTrait;

class ApiTest extends BaseTest
{
    use TestableBaseTrait;

    /**
     * Faker class.
     *
     * @var string
     */
    protected $faker = Foo\Faker::class;

    /**
     * Router group resource.
     *
     * @var string
     */
    protected $group = 'admin';

    /**
     * Base path config.
     *
     * @var string
     */
    protected $config = 'ore.faker';
}
