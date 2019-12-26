<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Util\StringUtil;

class MetaDescription extends FilterRule
{
    public function filter($value)
    {
        preg_replace('#^\s*<h\d+[^>]>(.*?)</h\d+>#usi', '', $value);
        $value = FilterStatic::filterValue($value, HtmlAndUnicode::class);
        $value = trim(preg_replace('#\s+#', ' ', $value));
        $value = preg_replace('#\s*([;:.,!?])#usi', '\\1', $value);

        return StringUtil::truncate($value, 200, true, '');
    }
}