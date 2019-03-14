<?php

namespace Railken\Amethyst\Api\Transformers;

use Doctrine\Common\Inflector\Inflector;
use Illuminate\Http\Request;
use League\Fractal\TransformerAbstract;
use Railken\Amethyst\Api\Concerns\ApiTransformerTrait;
use Railken\Amethyst\Api\Contracts\TransformerContract;
use Railken\Lem\Attributes\BelongsToAttribute;
use Railken\Lem\Contracts\EntityContract;
use Railken\Lem\Contracts\ManagerContract;
use Railken\Lem\Tokens;
use Railken\EloquentMapper\Mapper;
use Illuminate\Support\Facades\Config;

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
     * @param \Railken\Lem\Contracts\EntityContract  $entity
     * @param \Illuminate\Http\Request               $request
     */
    public function __construct(ManagerContract $manager, EntityContract $entity = null, Request $request)
    {
        $this->manager = $manager;
        $this->inflector = new Inflector();
        $this->request = $request;

        $this->availableIncludes = Mapper::mapKeysRelation(get_class($entity));

        $this->setSelectedAttributes($this->getSelectedAttributesByRequest($request));

        if ($entity) {
            $this->setAuthorizedAttributes($this->manager->getAuthorizer()->getAuthorizedAttributes(Tokens::PERMISSION_SHOW, $entity)->keys()->toArray());
        }
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

        $nameRelation = str_replace('_', '-', $this->inflector->tableize(get_class($relation)));

        $manager = null;

        foreach (array_keys(Config::get('amethyst')) as $config) {

            foreach (Config::get('amethyst.'.$config.'.data', []) as $data) {
                if (isset($data['model']) && $relation instanceof $data['model']) {
                    $classManager = $data['manager'];
                    $manager = new $classManager($this->manager->getAgent());
                    break;
                }
            }

        }

        return $relation && $manager ? $this->item(
            $relation, 
            new BaseTransformer($manager, $relation, $this->request),
            str_replace('_', '-', $this->inflector->tableize($manager->getName()))
        ) : null;
    }
}
