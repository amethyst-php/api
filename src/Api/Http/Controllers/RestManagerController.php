<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Railken\Lem\Attributes;
use Railken\Lem\Contracts\ManagerContract;

abstract class RestManagerController extends RestController
{
    /**
     * @var string
     */
    public $class;

    protected $startingQuery;

    protected $defaultNestedRelations = 1;

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

        $queryable = $this->retrieveNestedAttributes($query, $this->manager, $this->defaultNestedRelations);

        $this->queryable     = !empty($this->queryable) ? $this->queryable : $queryable;
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

    public function retrieveNestedAttributes($query, ManagerContract $manager, int $level = 2, array $prefixes = []): array
    {
        $attributes = [];

        foreach ($manager->getAttributes() as $attribute) {
            if ($attribute instanceof Attributes\MorphToAttribute && $level > 0) {
                
            } else if ($attribute instanceof Attributes\BelongsToAttribute && $level > 0) {
                $relationName        = $attribute->getRelationName();
                $relationatedManager = $attribute->getRelationManager($manager->newEntity());

                $belongsToRelation = $manager->newEntity()->$relationName();

                $ownerTable      = implode('.', array_merge([$relationName], $prefixes));
                $foreignPrefixes = !empty($prefixes) ? $prefixes : [$manager->newEntity()->getTable()];

                $query->leftJoin(
                    DB::raw('`'.$relationatedManager->newEntity()->getTable().'` as `'.$ownerTable.'`'),
                    DB::raw('`'.$ownerTable.'`.`'.$belongsToRelation->getOwnerKey().'`'),
                    '=',
                    DB::raw('`'.implode('.', $foreignPrefixes).'`.`'.$belongsToRelation->getForeignKey().'`')
                );

                $relationatedAttributes = $this->retrieveNestedAttributes(
                    $query,
                    $relationatedManager,
                    $level - 1,
                    array_merge([$relationName], $prefixes)
                );

                $attributes = array_merge($attributes, $relationatedAttributes);
            }

            $attributes[] = implode('.', array_merge($prefixes, [$attribute->getName()]));
        }

        return $attributes;
    }
}
