<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use \LogicException;
use Evirma\Bundle\CoreBundle\Service\LoggerService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @property ContainerInterface $container
 */
trait LoggerTrait
{
    /**
     * @var LoggerService
     */
    private $loggerTraitLogger;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if ($this->loggerTraitLogger) {
            return $this->loggerTraitLogger;
        }

        if (!isset($this->container) || !$this->container->has(LoggerService::class)) {
            throw new LogicException('The LoggerService is not registered in your application.');
        }

        $this->loggerTraitLogger = $this->container->get(LoggerService::class);
        return $this->loggerTraitLogger;
    }

    /**
     * @required
     * @param LoggerService $loggerService
     */
    public function setLogger(LoggerService $loggerService)
    {
        $this->loggerTraitLogger = $loggerService;
    }
}
