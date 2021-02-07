<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @property ContainerInterface $container
 */
trait ParserLoggerTrait
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
     * @param LoggerInterface $parserLogger
     */
    public function setLogger(LoggerInterface $parserLogger)
    {
        $this->logger = $parserLogger;
    }
}
