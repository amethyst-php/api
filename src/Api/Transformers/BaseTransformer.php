<?php

namespace Railken\Amethyst\Api\Transformers;

use Doctrine\Common\Inflector\Inflector;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use Railken\Amethyst\Api\Concerns\ApiTransformerTrait;
use Railken\Amethyst\Api\Contracts\TransformerContract;
use Railken\Amethyst\Api\Support\Helper;
use Railken\EloquentMapper\Mapper;
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

        $this->availableIncludes = Mapper::mapKeysRelation(get_class($manager->newEntity()));

        $this->setSelectedAttributes($this->getSelectedAttributesByRequest($request));

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
            $method = $this->inflector->tableize(preg_replace('/^include/', '', $method));

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
        $this->manager->getEntity();

        $entity = $args[0];

        $relations = Mapper::mapRelations(get_class($this->manager->newEntity()), function ($prefix, $relation) {
            return [$relation->name, [$relation]];
        }, 1);

        $relation = $entity->{$relationName};

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

        $manager = Helper::newManagerByModel($classRelation, $this->manager->getAgent());

        if (!$manager) {
            return null;
        }

        return $this->$method(
            $relation,
            new BaseTransformer($manager, $this->request),
            str_replace('_', '-', $this->inflector->tableize($manager->getName()))
        );
    }
}
