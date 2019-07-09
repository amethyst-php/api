<?php

namespace Amethyst\Api\Http\Controllers;

use Doctrine\Common\Inflector\Inflector;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use League\Fractal;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\TransformerAbstract;
use Amethyst\Api\Transformers\BaseTransformer;
use Railken\Lem\Contracts\EntityContract;
use Railken\EloquentMapper\Joiner;
use Railken\EloquentMapper\Mapper;
use Illuminate\Support\Facades\Cache;
use Railken\Cacheable\CacheableTrait;
use Railken\Cacheable\CacheableContract;
use Closure;
use Spatie\ResponseCache\Facades\ResponseCache;
use Railken\LaraEye\Filter;
use Railken\Lem\Attributes;


abstract class RestController extends Controller implements CacheableContract
{
    use CacheableTrait;

    public static $handlers;

    /**
     * Cache response?
     *
     * @var boolean
     */
    protected $cached = false;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $transformerClass = BaseTransformer::class;

    /**
     * @var \Railken\Lem\Contracts\ManagerContract
     */
    public $manager;

    /**
     * @var array
     */
    public $queryable = [];

    /**
     * @var array
     */
    public $fillable = [];

    public function __construct()
    {
        if ($this->cached) {
            $this->middleware(\Spatie\ResponseCache\Middlewares\CacheResponse::class);

        }
    }

    public function callAction($method, $parameters)
    {   
        $request = collect($parameters)->first(function ($item) {
            return $item instanceof Request;
        });

        $this->bootstrap($request);

        return $this->{$method}(...array_values($parameters));
    }
    
    /**
     * Retrieve resource name.
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->name !== null ? $this->name : str_replace('_', '-', (new Inflector())->tableize($this->getManager()->getName()));
    }

    /**
     * Return a new instance of Manager.
     *
     * @return \Railken\Lem\Contracts\ManagerContract
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function bootstrap(Request $request) 
    {
        if ($this->manager) {
            $this->manager->setAgent($this->getUser());

            $this->initializeQueryable($request);
            $this->initializeFillable($request);
        }

        if ($this->cached) {
            $entity = $this->manager->getEntity();

            $entity::created(function () {
                ResponseCache::clear();
            });

            $entity::updated(function () {
                ResponseCache::clear();
            });

            $entity::deleted(function () {
                ResponseCache::clear();
            });
        }
    }

    public function initializeQueryable(Request $request)
    {
        $query = $this->getManager()->getRepository()->getQuery();
            

        $relations = $this->retrieveNestedRelationsCached(strval($request->input('include')));


        $queryable = $this->retrieveNestedAttributesCached($relations);

        $this->queryable = !empty($this->queryable) ? $this->queryable : $queryable;
        $this->startingQuery = $query;

        $usedRelations = $this->getUsedRelationsByFilter($request);

        $joinedRelations = collect($relations)->filter(function ($relation) use ($usedRelations) {
            return $usedRelations->search($relation) !== false;
        })->toArray();

        $this->parseRelations($query, $joinedRelations, $relations);
        
    }

    public function getUsedRelationsByFilter(Request $request)
    {
        $filter = new Filter($this->manager->newEntity()->getTable(), $this->queryable);
        
        $relations = $this->extractFilterRelations($filter->getParser()->parse($request->input('query')));

        return collect($relations)->map(function ($element) {
            return implode(".", array_slice(explode(".", $element), 0, -1)); 
        })->filter(function ($element) {
            return !empty($element);
        });
    }

    public function extractFilterRelations($node)
    {
        $relations = [];

        if ($node instanceof \Railken\SQ\Languages\BoomTree\Nodes\KeyNode) {
            $relations[] = $node->getValue();
        }

        foreach ($node->getChildren() as $child) {

            $relations = array_merge($relations, $this->extractFilterRelations($child));
        }

        return $relations;
    }


    public function initializeFillable(Request $request)
    {
        $this->fillable = array_merge($this->fillable, $this->getFillable());
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
    public function retrieveNestedAttributes(array $relations): array
    {
        $attributes = $this->getManager()->getAttributeNames();

        foreach (app('eloquent.mapper')->getFinder()->resolveRelations($this->getManager()->getEntity(), $relations) as $key => $relation) {
            $manager = app('amethyst')->newManagerByModel($relation->model, $this->getManager()->getAgent());

            $attributes = $attributes->merge($manager->getAttributes()->map(function ($attribute) use ($key) {
                return $key.'.'.$attribute->getName();
            })->values());
        }
        return $attributes->toArray();
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

    /**
     * Create a new instance of fractal transformer.
     *
     * @param \Railken\Lem\Contracts\EntityContract $entity
     * @param \Illuminate\Http\Request              $request
     *
     * @return TransformerAbstract
     */
    public function getFractalTransformer(EntityContract $entity = null, Request $request): TransformerAbstract
    {
        $classTransformer = $this->transformerClass;

        return new $classTransformer($this->getManager(), $request);
    }

    /**
     * Retrieve url base.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string
     */
    public function getResourceBaseUrl(Request $request): string
    {
        return $request->getSchemeAndHttpHost().Config::get('amethyst.api.http.'.explode('.', Route::getCurrentRoute()->getName())[0].'.router.prefix');
    }

    /**
     * Retrieve fractal manager.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Fractal\Manager;
     */
    public function getFractalManager(Request $request)
    {
        $manager = new Fractal\Manager();
        $manager->setSerializer(new JsonApiSerializer());

        if ($request->input('include') !== null) {
            $manager->parseIncludes($request->input('include'));
        }

        return $manager;
    }

    /**
     * Serialize entity.
     *
     * @param \Railken\Lem\Contracts\EntityContract $entity
     * @param \Illuminate\Http\Request              $request
     *
     * @return array
     */
    public function serialize(EntityContract $entity, Request $request)
    {
        $transformer = $this->getFractalTransformer($entity, $request);

        $resource = new Fractal\Resource\Item($entity, $transformer, $this->getResourceName());

        return $this->getFractalManager($request)->createData($resource)->toArray();
    }

    /**
     * Serialize a collection.
     *
     * @param Collection               $collection
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $paginator
     *
     * @return array
     */
    public function serializeCollection(Collection $collection, Request $request, $paginator = null)
    {
        $transformer = $this->getFractalTransformer($collection->get(0), $request);

        $resource = new Fractal\Resource\Collection($collection, $transformer, $this->getResourceName());

        if ($paginator) {
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }

        return $this->getFractalManager($request)->createData($resource)->toArray();
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
            return;
        }
    }


    public function retrieveNestedRelations(string $include): array
    {
        return Collection::make(explode(',', $include))
            ->filter(function ($item) {
                return app('eloquent.mapper')->getFinder()->isValidNestedRelation($this->getManager()->getEntity(), $item);
            })
            ->toArray();
    }

    public function parseRelations($query, array $joinedRelatinos, array $relations)
    {
        $joiner = new Joiner($query);

        foreach ($relations as $relation) {
            $query->with($relation);
        }

        foreach ($joinedRelatinos as $relation) {
            $joiner->joinRelations($relation);
        }

        self::executeHandlers('query', (object)[
            'manager' => $this->manager, 
            'query' => $query
        ]);
    }
    
    public function getEntityById(int $id)
    {
        return $this->getQuery()->where($this->manager->newEntity()->getTable().'.id', $id)->first();
    }
}
