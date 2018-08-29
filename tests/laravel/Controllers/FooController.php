<?php

namespace Controllers;

use Foo\FooManager;
use Railken\LaraOre\Api\Http\Controllers\RestController;
use Railken\LaraOre\Api\Http\Controllers\Traits as RestTraits;
use Transformers\FooTransformer;

class FooController extends RestController
{
    use RestTraits\RestIndexTrait;
    use RestTraits\RestCreateTrait;
    use RestTraits\RestUpdateTrait;
    use RestTraits\RestShowTrait;
    use RestTraits\RestRemoveTrait;

    public $name = 'foo';

    public $transformerClass = FooTransformer::class;

    public $queryable = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    public $fillable = [
        'name',
    ];

    /**
     * Construct.
     */
    public function __construct(FooManager $manager)
    {
        $this->manager = $manager;
        $this->manager->setAgent($this->getUser());

        parent::__construct();
    }

    /**
     * Create a new instance for query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery()
    {
        return $this->manager->repository->getQuery();
    }
}
