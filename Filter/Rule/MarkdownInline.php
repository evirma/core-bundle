<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Ext\InlinesOnly\InlinesOnlyExtension;

class MarkdownInline extends HtmlSanitizeInline
{
    /** @var CommonMarkConverter */
    private $markdownParser;

    public function filter($value)
    {
        return parent::filter($this->getMarkdownParser()->convertToHtml($value));
    }

    /**
     * @return CommonMarkConverter
     */
    private function getMarkdownParser()
    {
        if (!$this->markdownParser) {
            $environment = new Environment();
            $environment->addExtension(new InlinesOnlyExtension());

            $this->markdownParser = new CommonMarkConverter(
                [
                    'renderer' => [
                        'block_separator' => "\n",
                        'inner_separator' => "\n",
                        'soft_break' => "\n",
                    ],
                    'enable_em' => true,
                    'enable_emphasis' => true,
                    'enable_strong' => true,
                    'use_asterisk' => true,
                    'use_underscore' => true,
                    'html_input' => 'strip',
                    'allow_unsafe_links' => true,
                ], $environment
            );
        }

        return $this->markdownParser;
    }
}
