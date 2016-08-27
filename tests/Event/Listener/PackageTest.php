<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Event\Listener;

use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed;
use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters;
use AnimeDb\Bundle\ApiClientBundle\Service\Client;
use AnimeDb\Bundle\AppBundle\Entity\Plugin;
use AnimeDb\Bundle\AppBundle\Event\Listener\Package;
use AnimeDb\Bundle\AppBundle\Service\Downloader;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class PackageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Client
     */
    protected $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Downloader
     */
    protected $downloader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectRepository
     */
    protected $rep;

    /**
     * @var Package
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Parameters
     */
    protected $parameters;

    protected function setUp()
    {
        $this->parameters = $this
            ->getMockBuilder('\AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters')
            ->disableOriginalConstructor()
            ->getMock();
        $this->client = $this
            ->getMockBuilder('\AnimeDb\Bundle\ApiClientBundle\Service\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->downloader = $this
            ->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\Downloader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this
            ->getMockBuilder('\Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->rep = $this
            ->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with('AnimeDbAppBundle:Plugin')
            ->will($this->returnValue($this->rep));

        $this->listener = new Package($this->em, $this->client, $this->downloader, $this->parameters);
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return [
            ['onInstalled', '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed'],
            ['onUpdated', '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Updated']
        ];
    }

    /**
     * @dataProvider getEvents
     *
     * @param string $method
     * @param string $event
     */
    public function testIgnorePackage($method, $event)
    {
        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('foo'));
        $this->em
            ->expects($this->never())
            ->method('persist');

        // test
        call_user_func([$this->listener, $method], $this->getEvent($package, $event));
    }

    /**
     * @return array
     */
    public function getPlugins()
    {
        $events = $this->getEvents();
        $plugins = [];
        foreach ($events as $event) {
            $plugins[] = array_merge($event, [[
                'logo' => '',
                'title' => 'plugin title',
                'description' => 'plugin description'
            ]]);
            $plugins[] = array_merge($event, [[
                'logo' => '/path/to/image/logo.jpg',
                'title' => 'plugin title',
                'description' => 'plugin description'
            ]]);
        }

        return $plugins;
    }

    /**
     * @dataProvider getPlugins
     *
     * @param string $method
     * @param string $event
     * @param array $data
     */
    public function testUpdatePlugin($method, $event, array $data) {
        $plugin = $this->getPlugin($data);
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($plugin))
            ->with('foo/bar');
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($plugin);
        $this->em
            ->expects($this->once())
            ->method('flush');
        $this->client
            ->expects($this->once())
            ->method('getPlugin')
            ->with('foo', 'bar')
            ->will($this->returnValue($data));

        // test
        call_user_func([$this->listener, $method], $this->getEvent($this->getPackage(), $event));
    }

    /**
     * @dataProvider getPlugins
     *
     * @param string $method
     * @param string $event
     * @param array $data
     */
    public function testAddNewPlugin($method, $event, array $data) {
        $that = $this;
        // check download logo
        if ($data['logo']) {
            $this->downloader
                ->expects($this->once())
                ->method('entity')
                ->will($this->returnCallback(function ($logo, $plugin, $override) use ($that, $data) {
                    $that->assertEquals($data['logo'], $logo);
                    $that->assertTrue($override);
                    $that->checkNewPlugin($plugin, $data);
                }));
        }
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null))
            ->with('foo/bar');
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->will($this->returnCallback(function ($plugin) use ($that, $data) {
                $that->checkNewPlugin($plugin, $data);
            }));
        $this->em
            ->expects($this->once())
            ->method('flush');
        $this->client
            ->expects($this->once())
            ->method('getPlugin')
            ->with('foo', 'bar')
            ->will($this->returnValue($data));

        // test
        call_user_func([$this->listener, $method], $this->getEvent($this->getPackage($this->exactly(2)), $event));
    }

    /**
     * @param Plugin $plugin
     * @param array $data
     */
    public function checkNewPlugin($plugin, array $data)
    {
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Entity\Plugin', $plugin);
        $this->assertEquals('foo/bar', $plugin->getName());
        $this->assertEquals($data['title'], $plugin->getTitle());
        $this->assertEquals($data['description'], $plugin->getDescription());
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation|null $matcher
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPackage(\PHPUnit_Framework_MockObject_Matcher_Invocation $matcher = null)
    {
        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(Package::PLUGIN_TYPE));
        $package
            ->expects($matcher ?: $this->once())
            ->method('getName')
            ->will($this->returnValue('foo/bar'));
        return $package;
    }

    /**
     * @param array $data
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPlugin(array $data = [])
    {
        $plugin = $this->getMock('\AnimeDb\Bundle\AppBundle\Entity\Plugin');
        $plugin
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('foo/bar'));

        $setters = [
            'title' => 'setTitle',
            'description' => 'setDescription'
        ];
        foreach ($setters as $key => $method) {
            if(empty($data[$key])) {
                $plugin
                    ->expects($this->never())
                    ->method($method);
            } else {
                $plugin
                    ->expects($this->once())
                    ->method($method)
                    ->with($data[$key])
                    ->will($this->returnSelf());
            }
        }
        $this->downloader
            ->expects(!empty($data['logo']) ? $this->once() : $this->never())
            ->method('entity')
            ->with($data['logo'], $plugin, true);

        return $plugin;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $package
     * @param string $event
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Removed
     */
    protected function getEvent(\PHPUnit_Framework_MockObject_MockObject $package, $event)
    {
        $event = $this->getMockBuilder($event)
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->atLeastOnce())
            ->method('getPackage')
            ->will($this->returnValue($package));

        return $event;
    }

    public function testOnRemovedIgnorePackage()
    {
        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('foo'));
        $this->em
            ->expects($this->never())
            ->method('find');

        // test
        $this->listener->onRemoved($this->getEvent($package, '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed'));
    }

    public function testOnRemovedNoFoundPlugin()
    {
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null))
            ->with('foo/bar');

        // test
        $event = '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed';
        $this->listener->onRemoved($this->getEvent($this->getPackage(), $event));
    }

    public function testOnRemovedRemovePlugin()
    {
        $plugin = $this->getMock('\AnimeDb\Bundle\AppBundle\Entity\Plugin');
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($plugin))
            ->with('foo/bar');
        $this->em
            ->expects($this->once())
            ->method('remove')
            ->with($plugin);
        $this->em
            ->expects($this->once())
            ->method('flush');

        // test
        $event = '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed';
        $this->listener->onRemoved($this->getEvent($this->getPackage(), $event));
    }

    /**
     * @return array
     */
    public function getShmopEvents()
    {
        return [
            ['onInstalledConfigureShmop', '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed'],
            ['onRemovedShmop', '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed']
        ];
    }

    /**
     * @dataProvider getShmopEvents
     *
     * @param string $method
     * @param string $event
     */
    public function testOnInstalledIgnoreConfigureShmop($method, $event)
    {
        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('foo'));
        $event = $this->getMockBuilder($event)
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getPackage')
            ->will($this->returnValue($package));
        $this->parameters
            ->expects($this->never())
            ->method('save');

        // test
        call_user_func([$this->listener, $method], $event);
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return [
            [
                'onInstalledConfigureShmop',
                '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed',
                [
                    'cache_time_keeper.driver' => 'cache_time_keeper.driver.multi',
                    'cache_time_keeper.driver.multi.fast' => 'cache_time_keeper.driver.shmop'
                ]
            ],
            [
                'onRemovedShmop',
                '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed',
                [
                    'cache_time_keeper.driver' => 'cache_time_keeper.driver.file'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getParameters
     *
     * @param string $method
     * @param string $event
     * @param array $expected
     */
    public function testOnInstalledConfigureShmop($method, $event, array $expected)
    {
        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(Package::PACKAGE_SHMOP));
        $event = $this->getMockBuilder($event)
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getPackage')
            ->will($this->returnValue($package));
        $index = 0;
        foreach ($expected as $key => $value) {
            $this->parameters
                ->expects($this->at($index))
                ->method('set')
                ->with($key, $value);
            $index++;
        }

        // test
        call_user_func([$this->listener, $method], $event);
    }
}
