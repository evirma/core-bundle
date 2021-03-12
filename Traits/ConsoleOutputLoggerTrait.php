<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @property ContainerInterface $container
 */
trait ConsoleOutputLoggerTrait
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
     * @param LoggerInterface $consoleOutputLogger
     */
    public function setLogger(LoggerInterface $consoleOutputLogger)
    {
        $this->logger = $consoleOutputLogger;
    }
}
