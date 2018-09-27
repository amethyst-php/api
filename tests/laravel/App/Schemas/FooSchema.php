<?php

namespace App\Schemas;

use Railken\Lem\Attributes;
use Railken\Lem\Schema;

class FooSchema extends Schema
{
    /**
     * Get all attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return [
            Attributes\IdAttribute::make(),
            Attributes\TextAttribute::make('name'),
            Attributes\TextAttribute::make('description')->setMaxLength(4096),
            Attributes\CreatedAtAttribute::make(),
            Attributes\UpdatedAtAttribute::make(),
        ];
    }
}
