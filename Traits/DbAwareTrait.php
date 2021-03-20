<?php

namespace Evirma\Bundle\CoreBundle\Traits;

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
}
