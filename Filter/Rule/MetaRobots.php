<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class MetaRobots extends FilterRule
{
    public function filter($value)
    {
        $entityLower = str_replace(' ', '', strtolower(trim($value)));

        switch ($entityLower) {
            case 'follow, noindex':
            case 'follow,noindex':
            case 'noindex, follow':
            case 'noindex,follow':
                return 'NOINDEX,FOLLOW';
            case 'index, nofollow':
            case 'index,nofollow':
            case 'nofollow, index':
            case 'nofollow,index':
                return 'INDEX,NOFOLLOW';
            case 'nofollow, noindex':
            case 'nofollow,noindex':
            case 'noindex, nofollow':
            case 'noindex,nofollow':
                return 'NOINDEX,NOFOLLOW';
            default:
                return 'INDEX,FOLLOW';
        }
    }
}