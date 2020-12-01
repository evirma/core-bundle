<?php

namespace Evirma\Bundle\CoreBundle\Pager\Adapter;

use function array_slice;
use function count;

/**
 * Adapter which calculates pagination from an array of items.
 */
class PagerArrayAdapter implements PagerAdapterInterface
{
    /**
     * @var array
     */
    private $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * Retrieves the array of items.
     *
     * @return array
     */
    public function getArray()
    {
        return $this->array;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->array);
    }

    /**
     * @param int $offset
     * @param int $length
     *
     * @return iterable
     */
    public function getItems($offset, $length): iterable
    {
        return array_slice($this->array, $offset, $length);
    }
}
