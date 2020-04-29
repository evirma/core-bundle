<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Html2Text\Html2Text;

class HtmlToText extends FilterRule
{
    public function filter($value)
    {
        $html = new Html2Text($value);

        return trim($html->getText());
    }
}