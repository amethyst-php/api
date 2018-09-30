<?php

namespace Railken\Amethyst\Api\Http\Controllers;

use Railken\Lem\Attributes;

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

        $this->manager->setAgent($this->getUser());

        parent::__construct();

        $this->middleware(function ($request, $next) {
            $this->manager->setAgent($this->getUser());

            return $next($request);
        });
    }
}
