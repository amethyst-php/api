<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Railken\Amethyst\Api\Support\Helper;
use Railken\EloquentMapper\Joiner;
use Railken\EloquentMapper\Mapper;
use Railken\Lem\Attributes;
use Illuminate\Support\Facades\Cache;
use Railken\Cacheable\CacheableTrait;
use Railken\Cacheable\CacheableContract;
use Closure;

abstract class RestManagerController extends RestController implements CacheableContract
{
    use CacheableTrait;

    public static $handlers;

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
            $this->manager->setAgent($this->getUser());

            $this->inializeQueryable($request);
            $this->initializeFillable($request);

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
            

        $relations = $this->retrieveNestedRelationsCached(strval($request->input('include')));

        $this->parseRelations($query, $relations);

        $queryable = $this->retrieveNestedAttributesCached($relations);

        $this->queryable = !empty($this->queryable) ? $this->queryable : $queryable;
        $this->startingQuery = $query;
        
    }

    public function initializeFillable(Request $request)
    {
        $this->fillable = array_merge($this->fillable, $this->getFillableCached());
    }

    public function getFillable()
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

        return $fillable;
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

    public function retrieveNestedRelations(string $include): array
    {
        return Collection::make(explode(',', $include))
            ->filter(function ($item) {
                return Mapper::isValidNestedRelationCached($this->getManager()->getEntity(), $item);
            })
            ->toArray();
    }

    public function parseRelations($query, array $relations)
    {
        $joiner = new Joiner($query);

        foreach ($relations as $relation) {
            $query->with($relation);
            $joiner->joinRelations($relation);
        }

        self::executeHandlers('query', (object)[
            'manager' => $this->manager, 
            'query' => $query
        ]);
    }

    public function retrieveNestedAttributes(array $relations): array
    {
        $attributes = $this->getManager()->getAttributeNames();

        foreach (Mapper::resolveRelationsCached($this->getManager()->getEntity(), $relations) as $key => $relation) {
            $manager = app('amethyst')->newManagerByModel($relation->model, $this->getManager()->getAgent());

            $attributes = $attributes->merge($manager->getAttributes()->map(function ($attribute) use ($key) {
                return $key.'.'.$attribute->getName();
            })->values());
        }
        return $attributes->toArray();
    }

    public static function iniHandler(string $name)
    {
        if (!isset(self::$handlers[$name])) {
            self::$handlers[$name] = [];
        }
    }

    public static function addHandler(string $name, Closure $closure)
    {
        self::iniHandler($name);
        self::$handlers[$name][] = $closure;
    }
    
    public static function executeHandlers(string $name, $data)
    {
        self::iniHandler($name);
        foreach (self::$handlers[$name] as $handler) {
            $handler($data);
        }
    }
}
