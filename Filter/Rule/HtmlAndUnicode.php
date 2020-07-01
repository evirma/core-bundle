<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class HtmlAndUnicode extends FilterRule
{
    public function filter($value)
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Double convert for &amp;quot;
        $value = strip_tags($value);

        $value = str_replace("&nbsp;", ' ', $value);
        $value = str_replace("&amp;", '&', $value);
        $value = html_entity_decode($value);
        $value = preg_replace('/\\\u[A-F\d]{2,5}/si', '', $value);

        return trim(preg_replace('#\s+#usi', ' ', $value));
    }
}
