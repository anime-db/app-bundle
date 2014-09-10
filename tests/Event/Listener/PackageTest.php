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

        $this->listener = new Package($doctrine, $this->fs, $this->client, $this->parameters);
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
        // check upload logo
        if ($data['logo']) {
            $this->fs
                ->expects($this->once())
                ->method('mirror')
                ->with($data['logo']);
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
                $that->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Entity\Plugin', $plugin);
                $that->assertEquals('foo/bar', $plugin->getName());
                $that->assertEquals($data['title'], $plugin->getTitle());
                $that->assertEquals($data['description'], $plugin->getDescription());
                if ($data['logo']) {
                    $that->assertEquals(pathinfo($data['logo'], PATHINFO_BASENAME), $plugin->getLogo());
                }
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
     * Get package
     *
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
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
            'logo' => 'setLogo',
            'title' => 'setTitle',
            'description' => 'setDescription'
        ];
        foreach ($setters as $key => $method) {
            if (!empty($data[$key])) {
                // upload logo
                if ($key == 'logo') {
                    $plugin
                        ->expects($this->once())
                        ->method('getAbsolutePath')
                        ->willReturn('/absolute/path');
                    $this->fs
                        ->expects($this->once())
                        ->method('mirror')
                        ->with($data[$key], '/absolute/path');
                    $data[$key] = pathinfo($data[$key], PATHINFO_BASENAME);
                }
                $plugin
                    ->expects($this->once())
                    ->method($method)
                    ->with($data[$key])
                    ->willReturnSelf();
            } else {
                $plugin
                    ->expects($this->never())
                    ->method($method);
            }
        }

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
}
