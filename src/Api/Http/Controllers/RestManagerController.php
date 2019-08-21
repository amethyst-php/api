<?php

namespace Amethyst\Api\Http\Controllers;

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
