<?php

namespace Evirma\Bundle\CoreBundle\Pager\Adapter;

/**
 * Adapter which returns a fixed data set.
 *
 * Best used when you need to do a custom paging solution and don't want to implement a full adapter for a one-off use case.
 */
class PagerFixedAdapter implements PagerAdapterInterface
{
    /**
     * @var int
     */
    private $count;

    /**
     * @var iterable
     */
    private $results;

    /**
     * @param int      $count
     * @param iterable $results
     */
    public function __construct($count, $results)
    {
        $this->count = $count;
        $this->results = $results;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @param int $offset
     * @param int $length
     *
     * @return iterable
     */
    public function getItems($offset, $length): iterable
    {
        return $this->results;
    }
}
