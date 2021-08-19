<?php

namespace Evirma\Bundle\CoreBundle\Domain\Exception\Repository;

use Doctrine\DBAL\Driver\Exception;
use DomainException;
use JetBrains\PhpStorm\Pure;
use Throwable;

class SqlDriverException extends DomainException
{
    private $driverException;

    #[Pure] public function __construct($message, Throwable $exception)
    {
        $this->driverException = $exception;
        parent::__construct($message, $exception->getCode(), $exception);
    }

    /**
     * Returns the driver specific error code if given.
     * Returns null if no error code was given by the driver.
     *
     * @return int
     */
    public function getErrorCode()
    {
        if ($this->driverException instanceof Exception) {
            return $this->driverException->getCode();
        }

        return $this->getCode();
    }

    /**
     * Returns the SQLSTATE the driver was in at the time the error occurred, if given.
     *
     * Returns null if no SQLSTATE was given by the driver.
     *
     * @return string|null
     */
    public function getSQLState()
    {
        if ($this->driverException instanceof Exception) {
            return $this->driverException->getSQLState();
        }

        return null;
    }

}