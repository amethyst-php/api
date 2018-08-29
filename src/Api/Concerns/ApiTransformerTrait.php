<?php

namespace Railken\LaraOre\Api\Concerns;

use Railken\Bag;
use Railken\Laravel\Manager\Contracts\EntityContract;

trait ApiTransformerTrait
{
    protected $selectedAttributes = [];
    protected $authorizedAttributes = [];

    public function setSelectedAttributes(array $selectedAttributes = [])
    {
        $this->selectedAttributes = $selectedAttributes;

        return $this;
    }

    public function getSelectedAttributes()
    {
        return $this->selectedAttributes;
    }

    public function setAuthorizedAttributes(array $authorizedAttributes = [])
    {
        $this->authorizedAttributes = $authorizedAttributes;

        return $this;
    }

    public function getAuthorizedAttributes()
    {
        return $this->authorizedAttributes;
    }

    public function filterAttributes(array $attributes)
    {
        $attributes = new Bag($attributes);

        return $attributes->only($this->getSelectedAttributes())->only($this->getAuthorizedAttributes())->toArray();
    }

    /**
     * Turn this item object into a generic array.
     *
     * @return array
     */
    public function transformAttributes(EntityContract $entity)
    {
        return $entity->toArray();
    }

    /**
     * Turn this item object into a generic array.
     *
     * @return array
     */
    public function transform(EntityContract $entity)
    {
        return $this->filterAttributes($this->transformAttributes($entity));
    }
}
