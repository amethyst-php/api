<?php

namespace Railken\LaraOre\Api\Support;

class Sorter
{
    /**
     * List of sorting values.
     *
     * @var array
     */
    protected $values;

    /**
     * List of sorting keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * Set keys.
     *
     * @param array $keys
     *
     * @return $this
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;

        return $this;
    }

    /**
     * Perform the query and retrieve the information about pagination.
     *
     * @param string $name
     * @param string $direction
     *
     * @return $this
     */
    public function add($name, $direction)
    {
        if (!in_array($name, $this->keys)) {
            throw new Exceptions\InvalidSorterFieldException($name);
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new Exceptions\InvalidSorterDirectionException($direction);
        }

        $field = new SorterField();
        $field->setName($name);
        $field->setDirection($direction);
        $this->values[] = $field;
    }

    /**
     * Retrieve all sorting values.
     *
     * @return array
     */
    public function get()
    {
        return $this->values;
    }
}
