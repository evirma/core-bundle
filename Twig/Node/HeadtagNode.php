<?php

namespace Evirma\Bundle\CoreBundle\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

class HeadtagNode extends Node implements NodeOutputInterface
{
    /**
     * TwigNodeMarkdown constructor.
     *
     * @param Node   $body
     * @param int    $lineno
     * @param string $tag
     */
    public function __construct(Node $body, $lineno, $tag = 'headtag')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Compiler $compiler A Twig_Compiler instance
     */
    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('ob_start();'.PHP_EOL)
            ->subcompile($this->getNode('body'))
            ->write('$content = ob_get_clean();'.PHP_EOL)
            ->write('$content = str_replace(\'/>\', "/>\n", $content);'.PHP_EOL)
            ->write('preg_match("/^\s*/", $content, $matches);'.PHP_EOL)
            ->write('$lines = explode("\n", $content);'.PHP_EOL)
            ->write('$content = preg_replace(\'/^\' . $matches[0]. \'/\', "", $lines);'.PHP_EOL)
            ->write('$content = join("\n", $content);'.PHP_EOL)
            ->write('echo $this->env->getExtension(\'Evirma\\Bundle\\CoreBundle\\Twig\Extension\\StupidExtension\')->headtag($content);'.PHP_EOL);

    }
}
