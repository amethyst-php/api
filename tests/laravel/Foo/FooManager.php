<?php

namespace Foo;

use Railken\Laravel\Manager\Contracts\AgentContract;
use Railken\Laravel\Manager\ModelManager;
use Railken\Laravel\Manager\Tokens;

class FooManager extends ModelManager
{
    /**
     * Class name entity.
     *
     * @var string
     */
    public $entity = Foo::class;

    /**
     * List of all attributes.
     *
     * @var array
     */
    protected $attributes = [Attributes\Id\IdAttribute::class, Attributes\Name\NameAttribute::class, Attributes\CreatedAt\CreatedAtAttribute::class, Attributes\UpdatedAt\UpdatedAtAttribute::class, Attributes\DeletedAt\DeletedAtAttribute::class];

    /**
     * List of all exceptions.
     *
     * @var array
     */
    protected $exceptions = [
        Tokens::NOT_AUTHORIZED => Exceptions\FooNotAuthorizedException::class,
    ];

    /**
     * Construct.
     *
     * @param AgentContract $agent
     */
    public function __construct(AgentContract $agent = null)
    {
        $this->setRepository(new FooRepository($this));
        $this->setSerializer(new FooSerializer($this));
        $this->setValidator(new FooValidator($this));
        $this->setAuthorizer(new FooAuthorizer($this));

        parent::__construct($agent);
    }
}
