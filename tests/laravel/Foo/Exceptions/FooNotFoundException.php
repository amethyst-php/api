<?php

namespace Foo\Exceptions;

class FooNotFoundException extends FooException
{
    /**
     * The code to identify the error.
     *
     * @var string
     */
    protected $code = 'FOO_NOT_FOUND';

    /**
     * The message.
     *
     * @var string
     */
    protected $message = 'Not found';
}
