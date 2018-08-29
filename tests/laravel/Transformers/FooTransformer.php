<?php

namespace Transformers;

use League\Fractal\TransformerAbstract;
use Railken\LaraOre\Api\Concerns\ApiTransformerTrait;

class FooTransformer extends TransformerAbstract
{
    use ApiTransformerTrait;
}
