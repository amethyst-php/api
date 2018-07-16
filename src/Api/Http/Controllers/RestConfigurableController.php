<?php

namespace Railken\LaraOre\Api\Http\Controllers;

use Illuminate\Support\Facades\Config;

abstract class RestConfigurableController extends RestController
{
    
    /**
     * @var string
     */
    public $config;
    
    /**
     * Create a new instance
     */
    public function __construct()
    {
        $config = Config::get($this->config);

        $this->queryable = array_merge($this->queryable, array_keys($config['attributes']));
        $this->fillable = array_merge($this->fillable, array_keys($config['attributes']));
        $this->manager = new $config['manager'];
        $this->manager->setAgent($this->getUser());

        parent::__construct();

        $this->middleware(function ($request, $next) {
            $this->manager->setAgent($this->getUser());

            return $next($request);
        });
    }
}
