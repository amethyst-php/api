<?php

namespace Foo\Exceptions;

class FooNotAuthorizedException extends FooException
{
    /**
     * The code to identify the error.
     *
     * @var string
     */
    protected $code = 'FOO_NOT_AUTHORIZED';

    /**
     * The message.
     *
     * @var string
     */
    protected $message = "You're not authorized to interact with %s, missing %s permission";
}
