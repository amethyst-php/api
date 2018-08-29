<?php

namespace Railken\LaraOre\Api\Tests;

use Railken\Bag;
use Railken\LaraOre\Api\Support\Testing\TestableTrait;

class ApiTest extends BaseTest
{
    use TestableTrait;

    public function testBase()
    {
        $this->commonTest('/api/v1/ore/admin/foo', new Bag(['name' => 'foo']));
    }
}
