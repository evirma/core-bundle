<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @property ContainerInterface $container
 */
trait LoggerTrait
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @required
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
