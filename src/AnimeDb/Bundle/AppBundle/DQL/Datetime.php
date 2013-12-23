<?php
namespace AnimeDb\Bundle\AppBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * Datetime ::= "DATETIME" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 */
class Datetime extends FunctionNode
{
    private $firstDateExpression = null;
    private $secondDateExpression = null;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondDateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'DATETIME(%s, %s)',
            $this->firstDateExpression->dispatch($sqlWalker),
            $this->secondDateExpression->dispatch($sqlWalker)
        );
    }
}