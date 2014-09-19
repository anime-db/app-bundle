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

use AnimeDb\Bundle\AppBundle\Event\Listener\Package;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

/**
 * Test listener package
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class PackageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Filesystem
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fs;

    /**
     * API client
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * Downloader
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $downloader;

    /**
     * Entity manager
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * Entity repository
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rep;

    /**
     * Package listener
     *
     * @var \AnimeDb\Bundle\AppBundle\Event\Listener\Package
     */
    protected $listener;

    /**
     * Path to parameters
     *
     * @var string
     */
    protected $parameters;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->parameters = tempnam(sys_get_temp_dir(), 'test');
        $this->fs = $this->getMock('\Symfony\Component\Filesystem\Filesystem');
        $this->client = $this->getMockBuilder('\AnimeDb\Bundle\ApiClientBundle\Service\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $this->downloader = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\Downloader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->rep = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($this->em);
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with('AnimeDbAppBundle:Plugin')
            ->willReturn($this->rep);

        $this->listener = new Package($doctrine, $this->fs, $this->client, $this->downloader, $this->parameters);
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->parameters);
    }

    /**
     * Get events
     *
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
     * Test ignore package
     *
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
            ->willReturn('foo');
        $this->em
            ->expects($this->never())
            ->method('persist');

        // test
        call_user_func([$this->listener, $method], $this->getEvent($package, $event));
    }

    /**
     * Get plugins
     *
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
     * Test update plugin
     *
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
            ->willReturn($plugin)
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
            ->willReturn($data);

        // test
        call_user_func([$this->listener, $method], $this->getEvent($this->getPackage(), $event));
    }

    /**
     * Test add new plugin
     *
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
                ->willReturnCallback(function ($logo, $plugin, $override) use ($that, $data) {
                    $that->assertEquals($data['logo'], $logo);
                    $that->assertTrue($override);
                    $that->checkNewPlugin($plugin, $data);
                });
        }
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->willReturn(null)
            ->with('foo/bar');
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($plugin) use ($that, $data) {
                $that->checkNewPlugin($plugin, $data);
            });
        $this->em
            ->expects($this->once())
            ->method('flush');
        $this->client
            ->expects($this->once())
            ->method('getPlugin')
            ->with('foo', 'bar')
            ->willReturn($data);

        // test
        call_user_func([$this->listener, $method], $this->getEvent($this->getPackage($this->exactly(2)), $event));
    }

    /**
     * Check new plugin
     *
     * @param \AnimeDb\Bundle\AppBundle\Entity\Plugin $plugin
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
     * Get package
     *
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
            ->willReturn(Package::PLUGIN_TYPE);
        $package
            ->expects($matcher ?: $this->once())
            ->method('getName')
            ->willReturn('foo/bar');
        return $package;
    }

    /**
     * Get plugin
     *
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
            ->willReturn('foo/bar');

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
                    ->willReturnSelf();
            }
        }
        $this->downloader
            ->expects(!empty($data['logo']) ? $this->once() : $this->never())
            ->method('entity')
            ->with($data['logo'], $plugin, true);

        return $plugin;
    }

    /**
     * Get event
     *
     * @param \PHPUnit_Framework_MockObject_MockObject $package
     * @param string $event
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEvent(\PHPUnit_Framework_MockObject_MockObject $package, $event)
    {
        $event = $this->getMockBuilder($event)
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->atLeastOnce())
            ->method('getPackage')
            ->willReturn($package);
        return $event;
    }

    /**
     * Test ignore package on removed
     */
    public function testOnRemovedIgnorePackage()
    {
        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getType')
            ->willReturn('foo');
        $this->em
            ->expects($this->never())
            ->method('find');

        // test
        $this->listener->onRemoved($this->getEvent($package, '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed'));
    }

    /**
     * Test on removed no found plugin
     */
    public function testOnRemovedNoFoundPlugin()
    {
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->willReturn(null)
            ->with('foo/bar');

        // test
        $event = '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed';
        $this->listener->onRemoved($this->getEvent($this->getPackage(), $event));
    }

    /**
     * Test on removed remove plugin
     */
    public function testOnRemovedRemovePlugin()
    {
        $plugin = $this->getMock('\AnimeDb\Bundle\AppBundle\Entity\Plugin');
        $this->rep
            ->expects($this->once())
            ->method('find')
            ->willReturn($plugin)
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
     * Get shmop events
     *
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
     * Test on installed ignore configure shmop
     *
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
            ->willReturn('foo');
        $event = $this->getMockBuilder($event)
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);
        $this->fs
            ->expects($this->never())
            ->method('dumpFile');

        // test
        call_user_func([$this->listener, $method], $event);
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return [
            [
                'onInstalledConfigureShmop',
                '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed',
                [
                    'parameters' => []
                ],
                [
                    'parameters' => [
                        'cache_time_keeper.driver' => 'cache_time_keeper.driver.multi',
                        'cache_time_keeper.driver.multi.fast' => 'cache_time_keeper.driver.shmop'
                    ]
                ]
            ],
            [
                'onInstalledConfigureShmop',
                '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed',
                [
                    'parameters' => [
                        'cache_time_keeper.driver' => 'cache_time_keeper.driver.file',
                        'cache_time_keeper.driver.multi.fast' => 'cache_time_keeper.driver.memcache'
                    ]
                ],
                [
                    'parameters' => [
                        'cache_time_keeper.driver' => 'cache_time_keeper.driver.multi',
                        'cache_time_keeper.driver.multi.fast' => 'cache_time_keeper.driver.shmop'
                    ]
                ]
            ],
            [
                'onRemovedShmop',
                '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed',
                [
                    'parameters' => []
                ],
                [
                    'parameters' => [
                        'cache_time_keeper.driver' => 'cache_time_keeper.driver.file'
                    ]
                ]
            ],
            [
                'onRemovedShmop',
                '\AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed',
                [
                    'parameters' => [
                        'cache_time_keeper.driver' => 'cache_time_keeper.driver.multi'
                    ]
                ],
                [
                    'parameters' => [
                        'cache_time_keeper.driver' => 'cache_time_keeper.driver.file'
                    ]
                ]
            ]
        ];
    }

    /**
     * Test on installed configure shmop
     *
     * @dataProvider getParameters
     *
     * @param string $method
     * @param string $event
     * @param array $actual
     * @param array $expected
     */
    public function testOnInstalledConfigureShmop($method, $event, array $actual, array $expected)
    {
        file_put_contents($this->parameters, Yaml::dump($actual));

        $package = $this->getMockBuilder('\Composer\Package\Package')
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->expects($this->once())
            ->method('getName')
            ->willReturn(Package::PACKAGE_SHMOP);
        $event = $this->getMockBuilder($event)
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);
        $this->fs
            ->expects($this->once())
            ->method('dumpFile')
            ->with($this->parameters, Yaml::dump($expected));

        // test
        call_user_func([$this->listener, $method], $event);
    }
}
