<?php

namespace Evirma\Bundle\CoreBundle\Twig\TokenParser;

use Evirma\Bundle\CoreBundle\Twig\Node\PageMetaStorageNode;
use Twig\Token;

class PageMetaJavascriptTokenParser extends PageMetaStorageTokenParser
{
    protected $nodeClass = PageMetaStorageNode::class;
    protected $groupPrefix = 'javascript_';

    public function decideMarkdownEnd(Token $token)
    {
        return $token->test('endjavascript');
    }

    public function getTag()
    {
        return 'javascript';
    }
}