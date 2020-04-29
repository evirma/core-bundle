<?php

namespace Evirma\Bundle\CoreBundle\Form\Transformer\Mapping;

use Doctrine\ORM\Mapping\Annotation;
use Evirma\Bundle\CoreBundle\Traits\TransformerMappingTrait;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @Annotation
 * @Target("PROPERTY")
 * @property array $groups The groups that the constraint belongs to
 */
abstract class AbstractMapping implements DataTransformerInterface, Annotation, MappingInterface
{
    use TransformerMappingTrait;
}
