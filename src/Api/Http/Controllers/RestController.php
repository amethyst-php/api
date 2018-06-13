<?php

namespace Railken\LaraOre\Api\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Railken\Bag;

abstract class RestController extends Controller
{
    /**
     * @var Bag
     */
    public $keys;
    
    /**
     * @var \Railken\Laravel\Manager\Contracts\ManagerContract
     */
    public $manager;
    
    /**
     * @var array
     */
    public $queryable = [];
    
    /**
     * @var array
     */
    public $selectable = [];

    /**
     * @var array
     */
    public $sortable = [];

    /**
     * @var array
     */
    public $fillable = [];

    /**
     * Construct
     */
    public function __construct()
    {
        $this->keys = new Bag();
        $this->keys->queryable = $this->queryable;
        $this->keys->selectable = collect(empty($this->selectable) ? $this->queryable : $this->selectable);
        $this->keys->sortable = collect(empty($this->sortable) ? $this->queryable : $this->sortable);
        $this->keys->fillable = $this->fillable;
    }

    /**
     * Return a new instance of Manager.
     *
     * @return \Railken\Laravel\Manager\Contracts\ManagerContract
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Parse the key before using it in the query.
     *
     * @param string $key
     *
     * @return string
     */
    public function parseKey($key)
    {
        $keys = explode('.', $key);

        if (count($keys) === 1) {
            $keys = [$this->manager->repository->newEntity()->getTable(), $keys[0]];
        }

        return DB::raw('`'.implode('.', array_slice($keys, 0, -1)).'`.'.$keys[count($keys) - 1]);
    }

    /**
     * Serialize entity.
     *
     * @param mixed $record
     * @param array $select
     *
     * @return array
     */
    public function serialize($record, $select)
    {
        return $this
            ->manager
            ->serializer
            ->serialize($record, $select)
            ->all();
    }
}
