<?php

namespace Railken\Amethyst\Api\Concerns;

use Railken\Bag;
use Railken\Lem\Contracts\EntityContract;

trait ApiTransformerTrait
{
    protected $selectedAttributes   = [];
    protected $authorizedAttributes = [];

    public function setSelectedAttributes(array $selectedAttributes = [])
    {
        $this->selectedAttributes = $selectedAttributes;

        return $this;
    }

    public function getSelectedAttributes(): array
    {
        return $this->selectedAttributes;
    }

    public function setAuthorizedAttributes(array $authorizedAttributes = [])
    {
        $this->authorizedAttributes = $authorizedAttributes;

        return $this;
    }

    public function getAuthorizedAttributes(): array
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
