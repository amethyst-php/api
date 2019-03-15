<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Railken\Amethyst\Api\Support\Helper;
use Railken\EloquentMapper\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\Lem\Attributes;

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
        $this->inializeManager();
        $this->inializeQueryable();
        $this->initializeFillable();

        $this->manager->setAgent($this->getUser());

        parent::__construct();

        $this->middleware(function ($request, $next) {
            $this->manager->setAgent($this->getUser());

            return $next($request);
        });
    }

    public function inializeManager()
    {
        $class = $this->class;

        if (!class_exists($class)) {
            throw new \Exception(sprintf("Class %s doesn't exist", $class));
        }

        $this->manager = new $class();
    }

    public function inializeQueryable()
    {
        $query = $this->getManager()->getRepository()->getQuery();

        $queryable = $this->retrieveNestedAttributes($query);

        $this->queryable = !empty($this->queryable) ? $this->queryable : $queryable;
        $this->startingQuery = $query;
    }

    public function initializeFillable()
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

    public function retrieveNestedAttributes($query): array
    {
        $joiner = new Joiner($query);

        $attributes = $this->getManager()->getAttributes()->map(function ($attribute) {
            return $attribute->getName();
        })->values()->toArray();

        $attributes = array_merge($attributes, Mapper::mapRelations(get_class($this->getManager()->newEntity()), function ($prefix, $relation) use ($joiner, $query) {
            $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

            $joiner->joinRelations($key);

            $manager = Helper::newManagerByModel($relation->model, $this->getManager()->getAgent());

            return [$key, $manager->getAttributes()->map(function ($attribute) use ($key) {
                return $key.'.'.$attribute->getName();
            })->values()->toArray()];
        }));

        return $attributes;
    }
}
