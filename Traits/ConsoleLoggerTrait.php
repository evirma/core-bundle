<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @property ContainerInterface $container
 */
trait ConsoleLoggerTrait
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
     * @param LoggerInterface $consoleLogger
     */
    public function setLogger(LoggerInterface $consoleLogger)
    {
        $this->logger = $consoleLogger;
    }
}
