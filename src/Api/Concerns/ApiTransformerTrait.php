<?php

namespace Railken\Amethyst\Api\Concerns;

use Railken\Bag;
use Railken\Lem\Contracts\EntityContract;
use Illuminate\Support\Collection;

trait ApiTransformerTrait
{
    protected $selectedAttributes = [];
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

    /**
     * Turn this item object into a generic array.
     *
     * @return array
     */
    public function transform(EntityContract $entity)
    {
        // return $this->filterAttributes($this->transformAttributes($entity));
        return $this->manager->getSerializer()->serialize($entity, Collection::make(array_merge($this->getSelectedAttributes(), $this->getAuthorizedAttributes())))->toArray();
    }
}