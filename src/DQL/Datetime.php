<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * Datetime
 *
 * Datetime ::= "DATETIME" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 *
 * @package AnimeDb\Bundle\AppBundle
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Datetime extends FunctionNode
{
    /**
     * First date expression
     *
     * @var \Doctrine\ORM\Query\AST\SimpleArithmeticExpression
     */
    private $firstDateExpression = null;

    /**
     * Second date expression
     *
     * @var \Doctrine\ORM\Query\AST\SimpleArithmeticExpression
     */
    private $secondDateExpression = null;

    /**
     * (non-PHPdoc)
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::parse()
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * (non-PHPdoc)
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::getSql()
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'DATETIME(%s, %s)',
            $this->firstDateExpression->dispatch($sqlWalker),
            $this->secondDateExpression->dispatch($sqlWalker)
        );
    }
}