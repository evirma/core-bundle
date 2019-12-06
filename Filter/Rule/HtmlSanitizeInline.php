<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use HtmlSanitizer\Sanitizer;
use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class HtmlSanitizeInline extends FilterRule
{
    /** @var Sanitizer */
    private $sanitizer;

    public function filter($value)
    {
        return $this->getSanitize()->sanitize($value);
    }

    /**
     * @return Sanitizer
     */
    private function getSanitize()
    {
        if (!$this->sanitizer) {
            $this->sanitizer = Sanitizer::create([
                'extensions' => ['basic'],
                'tags' => [
                    'em' => [
                        'allowed_attributes' => ['class', 'title'],
                    ],
                    'i' => [
                        'allowed_attributes' => ['class', 'title'],
                    ],
                    'del' => [
                        'allowed_attributes' => ['class', 'title'],
                    ],
                    'small' => [
                        'allowed_attributes' => ['class'],
                    ],
                    'strong' => [
                        'allowed_attributes' => ['class'],
                    ],
                    'sub' => [
                        'allowed_attributes' => ['class'],
                    ],
                    'sup' => [
                        'allowed_attributes' => ['class'],
                    ],
                ],
            ]);
        }

        return $this->sanitizer;
    }
}
