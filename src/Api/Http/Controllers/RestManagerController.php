<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Railken\Amethyst\Api\Support\Helper;
use Railken\EloquentMapper\Joiner;
use Illuminate\Http\Request;
use Railken\EloquentMapper\Mapper;
use Railken\Lem\Attributes;
use Illuminate\Support\Collection;

abstract class RestManagerController extends RestController
{
    /**
     * @var string
     */
    public $class;

    protected $startingQuery;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next) {

            $this->inializeManager($request);
            $this->inializeQueryable($request);
            $this->initializeFillable($request);

            $this->manager->setAgent($this->getUser());

            return $next($request);
        });
    }

    public function inializeManager(Request $request)
    {
        $class = $this->class;

        if (!class_exists($class)) {
            throw new \Exception(sprintf("Class %s doesn't exist", $class));
        }

        $this->manager = new $class();
    }

    public function inializeQueryable(Request $request)
    {
        $query = $this->getManager()->getRepository()->getQuery();

        $queryable = $this->retrieveNestedAttributes($query, $request);

        $this->queryable = !empty($this->queryable) ? $this->queryable : $queryable;
        $this->startingQuery = $query;
    }

    public function initializeFillable(Request $request)
    {
        $fillable = [];

        $attributes = $this->manager->getAttributes()->filter(function ($attribute) {
            return $attribute->getFillable();
        });

        foreach ($attributes as $attribute) {
            if ($attribute instanceof Attributes\BelongsToAttribute) {
                $fillable = array_merge($fillable, [$attribute->getRelationName(), $attribute->getName()]);
            } else {
                $fillable[] = $attribute->getName();
            }
        }

        $this->fillable = array_merge($this->fillable, $fillable);
    }

    /**
     * Create a new instance for query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery()
    {
        return $this->startingQuery;
    }

    public function getEntityById(int $id)
    {
        return $this->getQuery()->where($this->manager->newEntity()->getTable().'.id', $id)->first();
    }

    public function retrieveNestedAttributes($query, Request $request): array
    {

        $attributes = $this->getManager()->getAttributes()->map(function ($attribute) {
            return $attribute->getName();
        })->values()->toArray();


        $relations = Collection::make(Mapper::mapKeysRelation(get_class($this->getManager()->newEntity())))
            ->filter(function ($item) use ($request) {
                return in_array($item, explode(",", $request->input('include')));
            })
            ->map(function ($item) use ($query) {
                $query->with($item);
            })
            ->toArray();

        $joiner = new Joiner($query);

        $attributes = array_merge($attributes, Mapper::mapRelations(get_class($this->getManager()->newEntity()), function ($prefix, $relation) use ($joiner, $relations) {
            $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

            if (!in_array($key, $relations)) {
                return;
            }

            $joiner->joinRelations($key);

            $manager = Helper::newManagerByModel($relation->model, $this->getManager()->getAgent());

            return [$key, $manager->getAttributes()->map(function ($attribute) use ($key) {
                return $key.'.'.$attribute->getName();
            })->values()->toArray()];
        }));

        return $attributes;
    }
}
