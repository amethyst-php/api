<?php

namespace Foo;

use Faker\Factory;
use Railken\Bag;
use Railken\Laravel\Manager\BaseFaker;

class FooFaker extends BaseFaker
{
    /**
     * @var string
     */
    protected $manager = FooManager::class;

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
