<?php
declare(strict_types=1);
namespace SorryDankogai;

use ast\Node;
use Hoa\Compiler\Llk\Parser;

class Analyzer
{
    private $regexParser;
    private $results;

    public function __construct(Parser $regexParser)
    {
        $this->regexParser = $regexParser;

        $this->results = new \SplStack();
    }

    public function analyze(Node $node, string $filename = null) : \SplStack
    {
        if (count($node->children) > 0) {
            foreach ($node->children as $child) {
                if ($child instanceof Node) {
                    $this->analyze($child, $filename);
                    continue;
                }
            }
        }

        if ($this->isPregMatchCall($node)) {
            if (isset($node->children['args'])) {
                /** @var Node $args */
                $args = $node->children['args'];
                $parameters = $args->children;

                if (is_string($parameters[0])) {
                    if ($this->isRegexAngry($parameters[0], $node, $filename)) {
                        $this->results->push($node);
                    }
                } else {
                    var_dump($filename, $parameters[0]);

                }

            }
        }

        return $this->results;
    }

    protected function isPregMatchCall(Node $node) : bool
    {
        if ($node->kind !== \ast\AST_CALL) {
            return false;
        }
        if (!$node->children) {
            return false;
        }
        if (isset($node->children['expr']) && $node->children['expr'] instanceof Node) {
            if ($node->children['expr']->kind === \ast\AST_NAME) {
                if ($node->children['expr']->children['name'] === 'preg_match') {
                    return true;
                }
            }
            return false;
        }
    }

    protected function isRegexAngry(string $regex, Node $node = null, $filename = null) : bool
    {
        try {

            $regexAst = $this->regexParser->parse($regex);

            if ($regexAst->getChildrenNumber() > 1) {
                throw new \UnexpectedValueException("Aaaa");
            }
            /** @var \Hoa\Compiler\Llk\TreeNode $regexNode */
            foreach ($regexAst->getChild(0)->getChildren() as $i => $regexNode) {
                if ($regexNode->getValue()['token'] === 'anchor') {
                    if ($regexNode->getValue()['value'] === '^') {
                        return true;
                    }
                    if ($regexNode->getValue()['value'] === '$') {
                        return true;
                    }
                }
            }
        } catch (\Hoa\Compiler\Exception\UnexpectedToken $hceu) {
            // throw new \RuntimeException($filename.":". $node->lineno . " " . $hceu->getMessage(), $hceu->getCode(), $hceu);
        }
        return false;
    }
}