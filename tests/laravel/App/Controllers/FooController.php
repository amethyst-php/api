<?php

namespace App\Controllers;

use App\Managers\FooManager;
use Railken\Amethyst\Api\Http\Controllers\RestController;
use Railken\Amethyst\Api\Http\Controllers\Traits as RestTraits;

class FooController extends RestController
{
    use RestTraits\RestIndexTrait;
    use RestTraits\RestCreateTrait;
    use RestTraits\RestUpdateTrait;
    use RestTraits\RestShowTrait;
    use RestTraits\RestRemoveTrait;

    /**
     * The attributes that are queryable.
     *
     * @var array
     */
    public $queryable = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are fillable.
     *
     * @var array
     */
    public $fillable = [
        'name',
    ];

    /**
     * Construct.
     */
    public function __construct(FooManager $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }
}
