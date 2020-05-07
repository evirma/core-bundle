<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use ErrorException;
use Evirma\Bundle\CoreBundle\Service\SetGetAccessor;
use Symfony\Bundle\MakerBundle\Str;

trait SetGetExtraTrait
{
    /**
     * @var array
     */
    protected array $extra = [];

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (!SetGetAccessor::setAttributeValue($this, $name, $value)) {
            $snakeCase = Str::asSnakeCase($name);
            $this->extra[$snakeCase] = $value;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $snakeCase = Str::asSnakeCase($name);

        return isset($this->extra[$snakeCase]);
    }

    /**
     * @param $name
     * @return mixed
     * @throws ErrorException
     */
    public function __get($name)
    {
        $snakeCase = Str::asSnakeCase($name);
        if (isset($this->extra[$snakeCase])) {
            return $this->extra[$snakeCase];
        }

        throw new ErrorException("Параметр «{$name}» не найдет", 0, E_NOTICE);
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }
}
