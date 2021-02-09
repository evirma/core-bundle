<?php

namespace Evirma\Bundle\CoreBundle\Monolog;

use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomActivationStrategy implements ActivationStrategyInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function isHandlerActivated(array $record): bool
    {
        if ($record['level'] == 100) {
            return false;
        }

        $exception = $record['context']['exception'] ?? null;

        if ($exception && $exception instanceof HttpException) {
            return false;
        }

        return $record['level'] >= Logger::NOTICE;
    }
}