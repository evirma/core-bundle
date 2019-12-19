<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Evirma\Bundle\CoreBundle\Service\DbService;

trait DbAwareTrait
{
    /**
     * @var DbService
     */
    protected $db;

    /**
     * @required
     * @param DbService $dbService
     */
    public function setDb(DbService $dbService)
    {
        $this->db = $dbService;
    }

    /**
     * @return DbService
     */
    protected function getDb()
    {
        return $this->db;
    }
}
