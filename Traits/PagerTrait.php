<?php

namespace Evirma\Bundle\CoreBundle\Traits;


use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerDoctrineORMAdapter;
use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerFixedAdapter;
use Evirma\Bundle\CoreBundle\Pager\Pager;

trait PagerTrait
{
    public function createArrayPager($page, $perPage, $items, $itemsCount)
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        $itemsCount = min($itemsCount, $perPage * 100);

        return (new Pager(new PagerFixedAdapter($itemsCount, $items)))
            ->setPerPage($perPage)
            ->setPage($page);
    }

    public function createNoLimitArrayPager($page, $perPage, $items, $itemsCount)
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        return (new Pager(new PagerFixedAdapter($itemsCount, $items)))
            ->setPerPage($perPage)
            ->setPage($page);
    }

    public function createQueryPager($query, $page, $perPage = 30)
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? (int)$perPage : 100;

        return (new Pager(new PagerDoctrineORMAdapter($query)))
            ->setPerPage($perPage)
            ->setPage($page);
    }
}
