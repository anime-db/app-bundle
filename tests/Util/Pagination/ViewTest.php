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

use AnimeDb\Bundle\AppBundle\Util\Pagination\View;

/**
 * Test view
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class ViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test get total
     */
    public function testGetTotal()
    {
        $config = $this->getMock('\AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration');
        $config
            ->expects($this->once())
            ->method('getTotalPages')
            ->willReturn('110');
        $this->assertEquals(110, (new View($config))->getTotal());
    }

    /**
     * Get fail nodes
     *
     * @return array
     */
    public function getFailNodes()
    {
        return [
            ['getFirst', 1],
            ['getPrev', 1],
            ['getNext', 110],
            ['getLast', 110]
        ];
    }

    /**
     * Test get node fail
     *
     * @dataProvider getFailNodes
     *
     * @param string $method
     */
    public function testGetNodeFail($method, $current_page)
    {
        $config = $this->getMock('\AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration');
        $config
            ->expects($this->any())
            ->method('getTotalPages')
            ->willReturn(110);
        $config
            ->expects($this->any())
            ->method('getCurrentPage')
            ->willReturn($current_page);

        $view = new View($config);
        $this->assertNull(call_user_func([$view, $method]));
    }
}
