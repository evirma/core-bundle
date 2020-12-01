<?php

namespace Evirma\Bundle\CoreBundle\Pager\Adapter;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use function count;

class PagerDoctrineORMAdapter implements PagerAdapterInterface
{
    /**
     * @var Paginator
     */
    private $paginator;

    /**
     * @param Query|QueryBuilder $query
     * @param bool               $fetchJoinCollection Whether the query joins a collection (true by default)
     * @param bool|null          $useOutputWalkers    Flag indicating whether output walkers are used in the paginator
     */
    public function __construct($query, $fetchJoinCollection = true, $useOutputWalkers = null)
    {
        $this->paginator = new Paginator($query, $fetchJoinCollection);
        $this->paginator->setUseOutputWalkers($useOutputWalkers);
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->paginator->getQuery();
    }

    /**
     * Returns whether the query joins a collection.
     *
     * @return bool
     */
    public function getFetchJoinCollection()
    {
        return $this->paginator->getFetchJoinCollection();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->paginator);
    }

    /**
     * @param int $offset
     * @param int $length
     * @return iterable
     * @throws Exception
     */
    public function getItems($offset, $length): iterable
    {
        $this->paginator->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->paginator->getIterator();
    }
}
