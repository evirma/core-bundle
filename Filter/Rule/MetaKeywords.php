<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Util\StringUtil;

class MetaKeywords extends FilterRule
{
    public function filter($value)
    {
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        $result = [];
        foreach ($value as $keyword) {
            $keyword = FilterStatic::filterValue($keyword, MetaTrim::class);
            $result[StringUtil::lower($keyword)] = $keyword;
        }

        return implode(', ', $result);
    }
}