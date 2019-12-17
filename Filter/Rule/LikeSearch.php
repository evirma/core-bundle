<?php

namespace App\Filter\Rule;

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

class LikeSearch extends FilterRule
{
    public function filter($value)
    {
        $value = trim($value);
        $value = preg_replace('#\s+#usi', '%', $value);

        return '%'.mb_strtolower($value, 'UTF-8').'%';
    }
}
