<?php

namespace Evirma\Bundle\CoreBundle\Pager;

use Evirma\Bundle\CoreBundle\Pager\Adapter\PagerAdapterInterface;
use ArrayIterator;
use Countable;
use Exception;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use Traversable;

class Pager implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var PagerAdapterInterface
     */
    private $adapter;
    /**
     * @var int
     */
    private $perPage = 10;
    /**
     * @var int
     */
    private $page = 1;
    /**
     * @var int
     */
    private $count;
    /**
     * @var iterable|null
     */
    private $items;

    public function __construct(PagerAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return PagerAdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @param int $perPage
     * @return $this
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }
    
    /**
     * @param int $page
     * @return $this
     */
    public function setPage(int $page = 1)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        if (null === $this->count) {
            $this->count = $this->getAdapter()->count();
        }

        return $this->count;
    }

    /**
     * @return iterable|mixed|null
     */
    public function getItems()
    {
        if (null === $this->items) {
            $this->items = $this->getItemsFromAdapter();
        }

        return $this->items;
    }

    /**
     * @return iterable|mixed|null
     */
    private function getItemsFromAdapter(): iterable
    {
        $offset = ($this->getPage() - 1) * $this->getPerPage();
        $length = $this->getPerPage();

        return $this->adapter->getItems($offset, $length);
    }

    /**
     * @return int
     */
    public function getPages()
    {
        $pages = (int) ceil($this->count() / $this->getPerPage());

        if (0 === $pages) {
            return 1;
        }

        return $pages;
    }

    /**
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->page > 1;
    }

    /**
     * @return int
     *
     * @throws LogicException if there is no previous page
     */
    public function getPreviousPage()
    {
        if (!$this->hasPreviousPage()) {
            throw new LogicException('There is no previous page.');
        }

        return $this->page - 1;
    }

    /**
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->page < $this->getPages();
    }

    /**
     * @return int
     *
     * @throws LogicException if there is no next page
     */
    public function getNextPage()
    {
        if (!$this->hasNextPage()) {
            throw new LogicException('There is no next page.');
        }

        return $this->page + 1;
    }

    /**
     * @return Traversable
     * @throws Exception
     */
    public function getIterator()
    {
        $results = $this->getItems();

        if ($results instanceof Iterator) {
            return $results;
        }

        if ($results instanceof IteratorAggregate) {
            return $results->getIterator();
        }

        return new ArrayIterator($results);
    }

    /**
     * @return iterable
     */
    public function jsonSerialize()
    {
        $results = $this->getItems();

        if ($results instanceof Traversable) {
            return iterator_to_array($results);
        }

        return $results;
    }

    /**
     * @deprecated
     * @return int
     */
    public function getNbPages()
    {
        return $this->count();
    }

    /**
     * @deprecated
     * @return int
     */
    public function getNbResults()
    {
        return $this->count();
    }

    /**
     * @deprecated
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->getPage();
    }
}
