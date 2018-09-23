<?php

namespace Foo;

use Faker\Factory;
use Railken\Bag;
use Railken\Lem\Faker as BaseFaker;

class Faker extends BaseFaker
{
    /**
     * @return \Railken\Bag
     */
    public function parameters()
    {
        $faker = Factory::create();

        $bag = new Bag();
        $bag->set('name', $faker->name);

        return $bag;
    }
}
