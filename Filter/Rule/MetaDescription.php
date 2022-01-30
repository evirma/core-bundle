<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;

class MetaDescription extends FilterRule
{
    public function filter($value)
    {
        if (is_null($value)) {
            return '';
        }

        $value = preg_replace('#^\s*<h\d+[^>]>(.*?)</h\d+>#usi', '\\1', $value);
        $value = strip_tags($value);
        $value = FilterStatic::filterValue($value, HtmlAndUnicode::class);
        $value = trim(preg_replace('#\s+#', ' ', $value));
        $value = preg_replace('#\s*([;:.,!?])#usi', '\\1', $value);
        return preg_replace('#[.]+#usi', '.', $value);
    }
}
