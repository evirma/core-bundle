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
    protected $extra = [];

    public function __set($name, $value)
    {
        if (!SetGetAccessor::setAttributeValue($this, Str::asCamelCase($name), $value)) {
            $this->extra[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (isset($this->extra[$name])) {
            return $this->extra[$name];
        }

        throw new ErrorException("Параметр «{$name}» не найдет", 0, E_NOTICE);
    }
}
