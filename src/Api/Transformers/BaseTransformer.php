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

        foreach ($this->manager->getAttributes() as $attribute) {
            if ($attribute instanceof BelongsToAttribute) {
                $this->availableIncludes[] = $attribute->getRelationName();
            }
        }

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
        $entity = $args[0];

        $attribute = $this->manager->getAttributes()->filter(function ($attribute) use ($relationName) {
            return $attribute instanceof BelongsToAttribute && $attribute->getRelationName() === $relationName;
        })->first();

        $relation = $entity->{$relationName};


        return $relation ? $this->item(
            $relation, 
            new BaseTransformer($attribute->getRelationManager($entity), $relation, $this->request),
            str_replace('_', '-', $this->inflector->tableize($attribute->getRelationManager($entity)->getName()))
        ) : null;
    }
}
