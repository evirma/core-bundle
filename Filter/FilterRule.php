<?php

namespace Evirma\Bundle\CoreBundle\Filter;

use Symfony\Component\Form\DataTransformerInterface;

abstract class FilterRule implements DataTransformerInterface
{
    /**
     * @param $value
     * @return mixed
     */
    public function transform($value)
    {
        return $this->filter($value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract function filter($value);

    public function reverseTransform($value)
    {
        return $value;
    }
}