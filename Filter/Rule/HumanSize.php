<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Util\StringUtil;

class HumanSize extends Slug
{
    public function filter($value)
    {
        return StringUtil::humanSize($value, 1);
    }
}