<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class MetaRobots extends FilterRule
{
    public function filter($value)
    {
        $entityLower = str_replace(' ', '', strtolower(trim($value)));

        switch ($entityLower) {
            case 'follow,noindex':
                return 'FOLLOW,NOINDEX';
            case 'nofollow,index':
                return 'NOFOLLOW,INDEX';
            case 'nofollow,noindex':
                return 'NOFOLLOW,NOINDEX';
            default:
                return 'FOLLOW,INDEX';
        }
    }
}