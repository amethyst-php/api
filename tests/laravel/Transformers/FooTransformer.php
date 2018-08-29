<?php

namespace Transformers;

use League\Fractal\TransformerAbstract;
use Railken\LaraOre\Api\Concerns\ApiTransformerTrait;
use Railken\LaraOre\Api\Contracts\TransformerContract;

class FooTransformer extends TransformerAbstract implements TransformerContract
{
    use ApiTransformerTrait;
}
