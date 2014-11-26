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
     * Configuration
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * View
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\View
     */
    protected $view;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->config = $this->getMock('\AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration');
        $this->view = new View($this->config);
    }

    /**
     * Test get total
     */
    public function testGetTotal()
    {
        $this->config
            ->expects($this->once())
            ->method('getTotalPages')
            ->willReturn('110');
        $this->assertEquals(110, $this->view->getTotal());
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
        $this->config
            ->expects($this->any())
            ->method('getTotalPages')
            ->willReturn(110);
        $this->config
            ->expects($this->any())
            ->method('getCurrentPage')
            ->willReturn($current_page);

        $this->assertNull(call_user_func([$this->view, $method]));
    }

    /**
     * Get page links
     *
     * @return array
     */
    public function getPageLinks()
    {
        return [
            ['page_%s.html'],
            [function ($number) { return 'page_'.$number.'.html'; }],
        ];
    }

    /**
     * Get ferst page links
     *
     * @return array
     */
    public function getFerstPageLinks()
    {
        return [
            ['page_%s.html', ''],
            ['page_%s.html', '/index.html'],
            [function ($number) { return 'page_'.$number.'.html'; }, ''],
            [function ($number) { return 'page_'.$number.'.html'; }, '/index.html'],
        ];
    }

    /**
     * Get link
     *
     * @param string|callback $page_link
     * @param integer $number
     *
     * @return string
     */
    protected function getLink($page_link, $number)
    {
        return is_callable($page_link) ? call_user_func($page_link, $number) : sprintf($page_link, $number);
    }

    /**
     * Test get first
     *
     * @dataProvider getFerstPageLinks
     *
     * @param string|callback $page_link
     * @param string $ferst_page_link
     */
    public function testGetFirst($page_link, $ferst_page_link)
    {
        $this->config
            ->expects($this->once())
            ->method('getCurrentPage')
            ->willReturn(10);
        $this->config
            ->expects($ferst_page_link ? $this->atLeastOnce() : $this->once())
            ->method('getFerstPageLink')
            ->willReturn($ferst_page_link);
        $this->config
            ->expects($ferst_page_link ? $this->never() : $this->atLeastOnce())
            ->method('getPageLink')
            ->willReturn($page_link);

        $node = $this->view->getFirst();
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Util\Pagination\Node', $node);
        $this->assertEquals(1, $node->getPage());
        if ($ferst_page_link) {
            $this->assertEquals($ferst_page_link, $node->getLink());
        } else {
            $this->assertEquals($this->getLink($page_link, 1), $node->getLink());
        }
    }

    /**
     * Test get prev
     *
     * @dataProvider getPageLinks
     *
     * @param string|callback $page_link
     */
    public function testGetPrev($page_link)
    {
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getCurrentPage')
            ->willReturn(5);
        $this->config
            ->expects($this->never())
            ->method('getFerstPageLink')
            ->willReturn('');
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getPageLink')
            ->willReturn($page_link);

        $node = $this->view->getPrev();
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Util\Pagination\Node', $node);
        $this->assertEquals(4, $node->getPage());
        $this->assertEquals($this->getLink($page_link, 4), $node->getLink());
    }

    /**
     * Test get current
     *
     * @dataProvider getFerstPageLinks
     *
     * @param string|callback $page_link
     * @param string $ferst_page_link
     */
    public function testGetCurrent($page_link, $ferst_page_link)
    {
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getCurrentPage')
            ->willReturn(1);
        $this->config
            ->expects($ferst_page_link ? $this->atLeastOnce() : $this->once())
            ->method('getFerstPageLink')
            ->willReturn($ferst_page_link);
        $this->config
            ->expects($ferst_page_link ? $this->never() : $this->atLeastOnce())
            ->method('getPageLink')
            ->willReturn($page_link);

        $node = $this->view->getCurrent();
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Util\Pagination\Node', $node);
        $this->assertEquals(1, $node->getPage());
        if ($ferst_page_link) {
            $this->assertEquals($ferst_page_link, $node->getLink());
        } else {
            $this->assertEquals($this->getLink($page_link, 1), $node->getLink());
        }
    }

    /**
     * Test get next
     *
     * @dataProvider getPageLinks
     *
     * @param string|callback $page_link
     */
    public function testGetNext($page_link)
    {
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getCurrentPage')
            ->willReturn(5);
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getTotalPages')
            ->willReturn(10);
        $this->config
            ->expects($this->never())
            ->method('getFerstPageLink')
            ->willReturn('');
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getPageLink')
            ->willReturn($page_link);

        $node = $this->view->getNext();
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Util\Pagination\Node', $node);
        $this->assertEquals(6, $node->getPage());
        $this->assertEquals($this->getLink($page_link, 6), $node->getLink());
    }

    /**
     * Test get last
     *
     * @dataProvider getPageLinks
     *
     * @param string|callback $page_link
     */
    public function testGetLast($page_link)
    {
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getCurrentPage')
            ->willReturn(5);
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getTotalPages')
            ->willReturn(10);
        $this->config
            ->expects($this->never())
            ->method('getFerstPageLink')
            ->willReturn('');
        $this->config
            ->expects($this->atLeastOnce())
            ->method('getPageLink')
            ->willReturn($page_link);

        $node = $this->view->getLast();
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Util\Pagination\Node', $node);
        $this->assertEquals(10, $node->getPage());
        $this->assertEquals($this->getLink($page_link, 10), $node->getLink());
    }
}
