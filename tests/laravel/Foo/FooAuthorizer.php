<?php

namespace Foo;

use Railken\Laravel\Manager\ModelAuthorizer;
use Railken\Laravel\Manager\Tokens;

class FooAuthorizer extends ModelAuthorizer
{
    /**
     * List of all permissions.
     *
     * @var array
     */
    protected $permissions = [
        Tokens::PERMISSION_CREATE => 'foo.create',
        Tokens::PERMISSION_UPDATE => 'foo.update',
        Tokens::PERMISSION_SHOW   => 'foo.show',
        Tokens::PERMISSION_REMOVE => 'foo.remove',
    ];
}
