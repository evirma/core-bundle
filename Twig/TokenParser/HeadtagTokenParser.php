<?php

namespace Evirma\Bundle\CoreBundle\Twig\TokenParser;

use Evirma\Bundle\CoreBundle\Twig\Node\HeadtagNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Adds ability to inline markdown between tags.
 * {% headtag %}
 * This is **bold** and this _underlined_
 * 1. This is a bullet list
 * 2. This is another item in that same list
 * {% endheadtag %}
 */
class HeadtagTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideHeadtagEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new HeadtagNode($body, $lineno, $this->getTag());
    }

    /**
     * Decide if current token marks end of Markdown block.
     *
     * @param Token $token
     * @return bool
     */
    public function decideHeadtagEnd(Token $token)
    {
        return $token->test('endheadtag');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'headtag';
    }
}
