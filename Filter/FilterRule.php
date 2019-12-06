<?php

namespace Evirma\Bundle\CoreBundle\Filter;

abstract class FilterRule
{
    /**
     * @param mixed $value
     * @return mixed
     */
    abstract function filter($value);
}