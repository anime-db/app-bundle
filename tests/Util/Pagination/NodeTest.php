<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Util\Pagination;

use AnimeDb\Bundle\AppBundle\Util\Pagination\Node;

/**
 * Test node
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class NodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get nodes
     *
     * @return array
     */
    public function getNodes()
    {
        return array(
            array(1, '', false),
            array(4, 'http://example.com/?p=4', true),
        );
    }

    /**
     * Test node
     *
     * @dataProvider getNodes
     *
     * @param integer $page
     * @param string $link
     * @param boolean $is_current
     */
    public function test($page, $link, $is_current) {
        $node = new Node($page, $link, $is_current);
        $this->assertEquals($page, $node->getPage());
        $this->assertEquals($link, $node->getLink());
        $this->assertEquals($is_current, $node->isCurrent());
    }
}
