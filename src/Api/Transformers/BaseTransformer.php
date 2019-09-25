<?php

namespace Amethyst\Api\Transformers;

use Amethyst\Api\Concerns\ApiTransformerTrait;
use Amethyst\Api\Contracts\TransformerContract;
use Doctrine\Common\Inflector\Inflector;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use Railken\Lem\Contracts\ManagerContract;
use Railken\Lem\Tokens;

class BaseTransformer extends TransformerAbstract implements TransformerContract
{
    use ApiTransformerTrait;

    /**
     * Manager.
     *
     * @var \Railken\Lem\Contracts\ManagerContract
     */
    protected $manager;

    /**
     * Entity.
     *
     * @var \Railken\Lem\Contracts\EntityContract
     */
    protected $entity;

    /**
     * Http Request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Doctrine\Common\Inflector\Inflector
     */
    protected $inflector;

    protected $relationedTransformers = [];

    /**
     * Create a new instance.
     *
     * @param \Railken\Lem\Contracts\ManagerContract $manager
     * @param \Illuminate\Http\Request               $request
     */
    public function __construct(ManagerContract $manager, Request $request)
    {
        $this->manager = $manager;
        $this->inflector = new Inflector();
        $this->request = $request;

        $this->availableIncludes = Collection::make(explode(',', $request->input('include')))
            ->filter(function ($item) {
                return app('eloquent.mapper')->getFinder()->isValidNestedRelation($this->manager->getEntity(), $item);
            })
            ->toArray();

        // $this->setSelectedAttributes($this->getSelectedAttributesByRequest($request));

        // if ($entity) {
            // $this->setAuthorizedAttributes($this->manager->getAuthorizer()->getAuthorizedAttributes(Tokens::PERMISSION_SHOW, $entity)->keys()->toArray());
        // }
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (preg_match('/^include/', $method)) {
            $method = preg_replace('/^include/', '', $method);

            return $this->resolveInclude($method, $args);
        }

        trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
    }

    public function getSelectedAttributesByRequest(Request $request)
    {
        $selectable = $this->manager->getAttributes()->keys();

        $select = collect(explode(',', strval($request->input('select', ''))));

        if ($select->count() > 0) {
            $select = $selectable->filter(function ($attr) use ($select) {
                return $select->contains($attr);
            });
        }

        if ($select->count() == 0) {
            $select = $selectable;
        }

        return $select->toArray();
    }

    /**
     * Resolve an include using the manager.
     *
     * @param string $relationName
     * @param array  $args
     *
     * @return \League\Fractal\Resource\Item
     */
    public function resolveInclude(string $relationName, array $args)
    {
        $entity = $args[0];

        $relation = $entity->{$relationName};

        if (!$relation) {
            $relationName = $this->inflector->tableize($relationName);
            $relation = $entity->{$relationName};
        }

        if (!$relation) {
            return null;
        }

        if ($relation instanceof Collection) {
            if ($relation->count() === 0) {
                return null;
            }

            $classRelation = get_class($relation[0]);
            $method = 'collection';
        } else {
            $classRelation = get_class($relation);
            $method = 'item';
        }

        $manager = app('amethyst')->newManagerByModel($classRelation, $this->manager->getAgent());

        if (!$manager) {
            return null;
        }

        $transformer = $this->getTransformerByManager($relationName, $manager);

        return $this->$method(
            $relation,
            $transformer,
            str_replace('_', '-', $this->inflector->tableize($manager->getName()))
        );
    }

    public function getTransformerByManager($relationName, $manager)
    {
        if (!isset($this->relationedTransformers[$relationName])) {
            $this->relationedTransformers[$relationName] = new BaseTransformer($manager, $this->request);
        }

        return $this->relationedTransformers[$relationName];
    }
}
