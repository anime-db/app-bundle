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
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * Datetime
 *
 * Datetime ::= "DATETIME" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 */
class Datetime extends FunctionNode
{
    /**
     * First date expression
     *
     * @var SimpleArithmeticExpression
     */
    private $firstDateExpression = null;

    /**
     * Second date expression
     *
     * @var SimpleArithmeticExpression
     */
    private $secondDateExpression = null;

    /**
     * @param Parser $parser
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
     * @param SqlWalker $sqlWalker
     *
     * @return string
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
