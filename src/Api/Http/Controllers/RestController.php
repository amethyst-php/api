<?php

namespace Railken\LaraOre\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\Fractal;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\TransformerAbstract;
use Railken\Bag;
use Railken\Laravel\Manager\Contracts\EntityContract;
use Railken\Laravel\Manager\Tokens;

abstract class RestController extends Controller
{
    /**
     * @var Bag
     */
    public $keys;

    /**
     * @var \Railken\Laravel\Manager\Contracts\ManagerContract
     */
    public $manager;

    /**
     * @var array
     */
    public $queryable = [];

    /**
     * @var array
     */
    public $selectable = [];

    /**
     * @var array
     */
    public $sortable = [];

    /**
     * @var array
     */
    public $fillable = [];

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->keys = new Bag();
        $this->keys->queryable = $this->queryable;
        $this->keys->selectable = collect(empty($this->selectable) ? $this->queryable : $this->selectable);
        $this->keys->sortable = collect(empty($this->sortable) ? $this->queryable : $this->sortable);
        $this->keys->fillable = $this->fillable;
    }

    public function getResourceName()
    {
        return $this->name;
    }

    /**
     * Return a new instance of Manager.
     *
     * @return \Railken\Laravel\Manager\Contracts\ManagerContract
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Parse the key before using it in the query.
     *
     * @param string $key
     *
     * @return string
     */
    public function parseKey($key)
    {
        $keys = explode('.', $key);

        if (count($keys) === 1) {
            $keys = [$this->manager->repository->newEntity()->getTable(), $keys[0]];
        }

        return DB::raw('`'.implode('.', array_slice($keys, 0, -1)).'`.'.$keys[count($keys) - 1]);
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

    public function getFractalTransformer()
    {
        $classTransformer = $this->transformerClass;

        return new $classTransformer();
    }

    public function initializeFractalTransformer(TransformerAbstract $transformer, EntityContract $entity = null, Request $request)
    {
        if ($entity !== null) {
            $transformer->setSelectedAttributes($this->getSelectedAttributesByRequest($request));
            $transformer->setAuthorizedAttributes($this->getManager()->getAuthorizer()->getAuthorizedAttributes(Tokens::PERMISSION_SHOW, $entity)->keys()->toArray());
        }

        return $transformer;
    }

    public function getFractalManager(Request $request)
    {
        $manager = new Fractal\Manager();
        $manager->setSerializer(new JsonApiSerializer());

        if ($request->input('include')) {
            $manager->parseIncludes($request->input('include'));
        }

        return $manager;
    }

    public function getSelectedAttributesByRequest(Request $request)
    {
        $select = collect(explode(',', $request->input('select', '')));

        if ($select->count() > 0) {
            $select = $this->keys->selectable->filter(function ($attr) use ($select) {
                return $select->contains($attr);
            });
        }

        if ($select->count() == 0) {
            $select = $this->keys->selectable;
        }

        return $select->toArray();
    }

    public function serialize(EntityContract $entity, Request $request)
    {
        $transformer = $this->getFractalTransformer();
        $transformer = $this->initializeFractalTransformer($transformer, $entity, $request);

        $resource = new Fractal\Resource\Item($entity, $transformer, $this->getResourceName());

        return $this->getFractalManager($request)->createData($resource)->toArray();
    }

    public function serializeCollection(Collection $collection, Request $request)
    {
        $transformer = $this->getFractalTransformer();
        $transformer = $this->initializeFractalTransformer($transformer, $collection->get(0, null), $request);

        $resource = new Fractal\Resource\Collection($collection, $transformer, $this->getResourceName());

        return $this->getFractalManager($request)->createData($resource)->toArray();
    }
}
