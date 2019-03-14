<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Railken\Lem\Attributes;
use Railken\Lem\Contracts\ManagerContract;
use Railken\EloquentMapper\Mapper;
use Railken\EloquentMapper\Joiner;

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

        $attributes = Mapper::mapRelations(get_class($this->getManager()->newEntity()), function ($prefix, $relation) use ($joiner, $query) {

            $key = $prefix ? $prefix.'.'.$relation->name : $relation->name;

            $joiner->joinRelations($key);
            
            return [$key, $this->getManager()->getAttributes()->map(function ($attribute) use ($key) {
                return $key.".".$attribute->getName();
            })->toArray()];
        });

        return $attributes;
    }
}
