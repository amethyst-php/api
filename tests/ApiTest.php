<?php

namespace Railken\LaraOre\Api\Tests;

use Railken\LaraOre\Api\Support\Testing\TestableTrait;
use Illuminate\Support\Facades\Schema;
use Railken\Bag;

class ApiTest extends BaseTest
{	

	use TestableTrait;

   	public function testBase()
    {
        $this->commonTest("/api/v1/ore/admin/foo", new Bag(['name' => 'foo']));
    }
}
