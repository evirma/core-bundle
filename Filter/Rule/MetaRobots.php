<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class MetaRobots extends FilterRule
{
    public function filter($value)
    {
        $entityLower = str_replace(' ', '', strtolower(trim($value)));

        switch ($entityLower) {
            case 'noindex,follow':
                return 'NOINDEX,FOLLOW';
            case 'index,nofollow':
                return 'INDEX,NOFOLLOW';
            case 'noindex,nofollow':
                return 'NOINDEX,NOFOLLOW';
            default:
                return 'INDEX,FOLLOW';
        }
    }
}