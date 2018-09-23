<?php

namespace Foo;

use Railken\Lem\Authorizer as BaseAuthorizer;
use Railken\Lem\Tokens;

class Authorizer extends BaseAuthorizer
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
