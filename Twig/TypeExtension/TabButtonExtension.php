<?php

namespace Evirma\Bundle\CoreBundle\Twig\TypeExtension;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;

class TabButtonExtension extends TabExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ButtonType::class];
    }
}