<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;

class Ucfirst extends FilterRule
{
    public function filter($value)
    {
        $value = trim(FilterStatic::filterValue($value, HtmlAndUnicode::class));

        return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($value, 1, mb_strlen($value, 'UTF-8'), 'UTF-8');
    }
}