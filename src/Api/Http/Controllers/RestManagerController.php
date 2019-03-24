<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $this->middleware(function ($request, $next) {
            $this->inializeQueryable($request);
            $this->initializeFillable($request);

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
        })->values();

        $joiner = new Joiner($query);

        $relations = Collection::make(explode(',', $request->input('include')))
            ->filter(function ($item) {
                return Mapper::isValidNestedRelation($this->getManager()->getEntity(), $item);
            })
            ->map(function ($item) use ($query, $joiner) {
                $query->with($item);
                $joiner->joinRelations($item);

                return $item;
            })
            ->toArray();

        Mapper::resolveRelations($this->getManager()->getEntity(), $relations)
            ->map(function ($relation, $key) use (&$attributes) {
                $manager = Helper::newManagerByModel($relation->model, $this->getManager()->getAgent());

                $attributes = $attributes->merge($manager->getAttributes()->map(function ($attribute) use ($key) {
                    return $key.'.'.$attribute->getName();
                })->values());
            });

        return $attributes->toArray();
    }
}
