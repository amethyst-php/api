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
use Spatie\ResponseCache\Facades\ResponseCache;
use Railken\LaraEye\Filter;

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
        $this->initializeManager();

        parent::__construct();
    }

    public function initializeManager()
    {
        $class = $this->class;

        if (!class_exists($class)) {
            throw new \Exception(sprintf("Class %s doesn't exist", $class));
        }

        $this->manager = new $class();
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

}
