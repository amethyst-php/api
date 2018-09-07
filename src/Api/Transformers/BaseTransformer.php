<?php

namespace Railken\LaraOre\Api\Transformers;

use League\Fractal\TransformerAbstract;
use Railken\LaraOre\Api\Concerns\ApiTransformerTrait;
use Railken\LaraOre\Api\Contracts\TransformerContract;

class BaseTransformer extends TransformerAbstract implements TransformerContract
{
    use ApiTransformerTrait;
}
