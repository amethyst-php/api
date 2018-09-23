<?php

namespace Railken\Amethyst\Api\Transformers;

use League\Fractal\TransformerAbstract;
use Railken\Amethyst\Api\Concerns\ApiTransformerTrait;
use Railken\Amethyst\Api\Contracts\TransformerContract;

class BaseTransformer extends TransformerAbstract implements TransformerContract
{
    use ApiTransformerTrait;
}
