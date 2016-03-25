<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Event\Widget;

use AnimeDb\Bundle\AppBundle\Event\Widget\Get;
use AnimeDb\Bundle\AppBundle\Service\WidgetsContainer;

/**
 * Test get
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Widget
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class GetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WidgetsContainer
     */
    protected $container;

    /**
     * @var string
     */
    protected $place = 'foo';

    /**
     * @var Get
     */
    protected $event;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\WidgetsContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = new Get($this->container, $this->place);
    }

    /**
     * @return array
     */
    public function getGetterMethods()
    {
        return [
            ['container', 'getWidgetsContainer'],
            ['place', 'getPlace']
        ];
    }

    /**
     * @dataProvider getGetterMethods
     *
     * @param string $var
     * @param string $method
     */
    public function testGetters($var, $method)
    {
        $this->assertEquals($this->$var, call_user_func([$this->event, $method]));
    }

    /**
     * @return array
     */
    public function getRegistrMethods()
    {
        return [
            ['registr'],
            ['unregistr']
        ];
    }

    /**
     * @dataProvider getRegistrMethods
     *
     * @param string $method
     */
    public function testRegistr($method)
    {
        $this->container
            ->expects($this->once())
            ->method($method)
            ->will($this->returnValue('bar'))
            ->with($this->place, 'AcmeDemoBundle:Welcome:index');

        $this->assertEquals('bar', call_user_func([$this->event, $method], 'AcmeDemoBundle:Welcome:index'));
    }
}
