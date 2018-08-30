<?php

namespace Railken\LaraOre\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use League\Fractal;
use League\Fractal\Serializer\JsonApiSerializer;
use Railken\Bag;
use Railken\LaraOre\Api\Contracts\TransformerContract;
use Railken\LaraOre\Api\Transformers\BaseTransformer;
use Railken\Laravel\Manager\Contracts\EntityContract;
use Railken\Laravel\Manager\Tokens;

abstract class RestController extends Controller
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $transformerClass = BaseTransformer::class;

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
        $this->keys->set('queryable', $this->queryable);
        $this->keys->set('selectable', collect(empty($this->selectable) ? $this->queryable : $this->selectable));
        $this->keys->set('sortable', collect(empty($this->sortable) ? $this->queryable : $this->sortable));
        $this->keys->set('fillable', $this->fillable);
    }

    /**
     * Retrieve resource name.
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->name !== null ? $this->name : strtolower(str_replace('Controller', '', (new \ReflectionClass($this))->getShortName()));
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
            $keys = [$this->getManager()->getRepository()->newEntity()->getTable(), $keys[0]];
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
        return $this->getManager()->getRepository()->getQuery();
    }

    public function getFractalTransformer()
    {
        $classTransformer = $this->transformerClass;

        return new $classTransformer();
    }

    public function initializeFractalTransformer(TransformerContract $transformer, EntityContract $entity = null, Request $request)
    {
        if ($entity !== null) {
            $transformer->setSelectedAttributes($this->getSelectedAttributesByRequest($request));
            $transformer->setAuthorizedAttributes($this->getManager()->getAuthorizer()->getAuthorizedAttributes(Tokens::PERMISSION_SHOW, $entity)->keys()->toArray());
        }

        return $transformer;
    }

    public function getFractalManager(Request $request, $container = 'admin')
    {

        $manager = new Fractal\Manager();
        $manager->setSerializer(new JsonApiSerializer($request->getSchemeAndHttpHost().'/'.Config::get('ore.api.http.'.$container.'.router.prefix')));

        if ($request->input('include') !== null) {
            $manager->parseIncludes($request->input('include'));
        }

        return $manager;
    }

    public function getSelectedAttributesByRequest(Request $request)
    {
        $select = collect(explode(',', strval($request->input('select', ''))));

        if ($select->count() > 0) {
            $select = $this->keys->get('selectable')->filter(function ($attr) use ($select) {
                return $select->contains($attr);
            });
        }

        if ($select->count() == 0) {
            $select = $this->keys->get('selectable');
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
