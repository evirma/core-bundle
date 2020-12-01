<?php

namespace Evirma\Bundle\CoreBundle\Traits;


use Countable;
use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerDoctrineORMAdapter;
use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerFixedAdapter;
use Evirma\Bundle\CoreBundle\Pager\Pager;
use IteratorAggregate;
use JsonSerializable;

trait PagerTrait
{
    /**
     * @param $page
     * @param $perPage
     * @param $items
     * @param $itemsCount
     * @return Pager|Countable|IteratorAggregate|JsonSerializable
     */
    public function createArrayPager($page, $perPage, $items, $itemsCount)
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        $itemsCount = min($itemsCount, $perPage * 100);

        return (new Pager(new PagerFixedAdapter($itemsCount, $items)))
            ->setPerPage($perPage)
            ->setPage($page);
    }

    /**
     * @param $page
     * @param $perPage
     * @param $items
     * @param $itemsCount
     * @return Pager|Countable|IteratorAggregate|JsonSerializable
     */
    public function createNoLimitArrayPager($page, $perPage, $items, $itemsCount)
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        return (new Pager(new PagerFixedAdapter($itemsCount, $items)))
            ->setPerPage($perPage)
            ->setPage($page);
    }

    /**
     * @param     $query
     * @param     $page
     * @param int $perPage
     * @return Pager|Countable|IteratorAggregate|JsonSerializable
     */
    public function createQueryPager($query, $page, $perPage = 30)
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        return (new Pager(new PagerDoctrineORMAdapter($query)))
            ->setPerPage($perPage)
            ->setPage($page);
    }
}
