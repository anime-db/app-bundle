<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Util\Pagination;

use AnimeDb\Bundle\AppBundle\Util\Pagination\Node;

class NodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getNodes()
    {
        return [
            [1, '', false],
            [4, 'http://example.com/?p=4', true],
        ];
    }

    /**
     * @dataProvider getNodes
     *
     * @param int $page
     * @param string $link
     * @param bool $is_current
     */
    public function test($page, $link, $is_current)
    {
        $node = new Node($page, $link, $is_current);
        $this->assertEquals($page, $node->getPage());
        $this->assertEquals($link, $node->getLink());
        $this->assertEquals($is_current, $node->isCurrent());
    }
}
