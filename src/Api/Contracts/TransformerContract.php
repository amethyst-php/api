<?php

namespace Railken\LaraOre\Api\Contracts;

interface TransformerContract
{
    public function setSelectedAttributes(array $selectedAttributes = []);

    public function getSelectedAttributes(): array;

    public function setAuthorizedAttributes(array $authorizedAttributes = []);

    public function getAuthorizedAttributes(): array;
}
