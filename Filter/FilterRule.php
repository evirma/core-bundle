<?php

namespace Evirma\Bundle\CoreBundle\Filter;

use Evirma\Bundle\CoreBundle\Form\Transformer\Mapping\AbstractMapping;
use Symfony\Component\Form\DataTransformerInterface;

abstract class FilterRule extends AbstractMapping implements DataTransformerInterface
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
}