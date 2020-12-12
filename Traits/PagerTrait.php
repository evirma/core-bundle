<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerDoctrineORMAdapter;
use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerFixedAdapter;
use Evirma\Bundle\CoreBundle\Pager\Pager;

trait PagerTrait
{
    /**
     * @param $page
     * @param $perPage
     * @param $items
     * @param $itemsCount
     * @return Pager
     */
    public function createArrayPager($page, $perPage, $items, $itemsCount): Pager
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
     * @return Pager
     */
    public function createNoLimitArrayPager($page, $perPage, $items, $itemsCount): Pager
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
     * @return Pager
     */
    public function createQueryPager($query, $page, $perPage = 30): Pager
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        return (new Pager(new PagerDoctrineORMAdapter($query)))
            ->setPerPage($perPage)
            ->setPage($page);
    }
}
