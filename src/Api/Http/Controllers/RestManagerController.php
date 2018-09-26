<?php

namespace Railken\Amethyst\Api\Http\Controllers;

abstract class RestManagerController extends RestController
{
    /**
     * @var string
     */
    public $class;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $class = $this->class;

        if (!class_exists($class)) {
            throw new \Exception(sprintf("Class %s doesn't exist", $class));
        }

        $this->manager = new $class();

        $this->queryable = array_merge($this->queryable, $this->manager->getAttributes()->keys()->toArray());

        $this->fillable = array_merge($this->fillable, $this->manager->getAttributes()->filter(function ($attribute) {
            return $attribute->getFillable();
        })->keys()->toArray());

        $this->manager->setAgent($this->getUser());

        parent::__construct();

        $this->middleware(function ($request, $next) {
            $this->manager->setAgent($this->getUser());

            return $next($request);
        });
    }
}
