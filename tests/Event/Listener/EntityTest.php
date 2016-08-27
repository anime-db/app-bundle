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

use AnimeDb\Bundle\AppBundle\Event\Listener\Entity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Filesystem\Filesystem;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $root = '/foo/';

    /**
     * @var Entity
     */
    protected $listener;

    protected function setUp()
    {
        $this->fs = $this->getMock('\Symfony\Component\Filesystem\Filesystem');
        $this->listener = new Entity($this->fs, $this->root);
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return [
            ['postRemove'],
            ['postUpdate']
        ];
    }

    /**
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function testIgnoreEntity($method)
    {
        $args = $this
            ->getMockBuilder('\Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue(new \stdClass()));
        $this->fs
            ->expects($this->never())
            ->method('remove');
        // test
        call_user_func([$this->listener, $method], $args);
    }

    /**
     * @return array
     */
    public function getMethodsAndOldFiles()
    {
        return [
            ['postRemove', []],
            ['postRemove', ['file1', 'file2']],
            ['postUpdate', []],
            ['postUpdate', ['file1', 'file2']]
        ];
    }

    /**
     * @dataProvider getMethodsAndOldFiles
     *
     * @param string $method
     * @param array $files
     */
    public function testRemoveOldFiles($method, array $files)
    {
        $that = $this;
        $root = $this->root.'bar/';
        $entity = $this->getMock('\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface');
        $entity
            ->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue(''));
        $entity
            ->expects($this->once())
            ->method('getDownloadPath')
            ->will($this->returnValue('bar'));
        $entity
            ->expects($this->once())
            ->method('getOldFilenames')
            ->will($this->returnValue($files));

        $args = $this->getMockBuilder('\Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->atLeastOnce())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->fs
            ->expects($files ? $this->atLeastOnce() : $this->never())
            ->method('remove')
            ->will($this->returnCallback(function ($file) use ($files, $root, $that) {
                $filename = pathinfo($file, PATHINFO_BASENAME);
                $that->assertContains($filename, $files);
                $that->assertEquals($root.$filename, $file);
            }));

        // test
        call_user_func([$this->listener, $method], $args);
    }

    /**
     * @return array
     */
    public function getOldFiles()
    {
        return [
            [[]],
            [['file1', 'file2']]
        ];
    }

    /**
     * @dataProvider getOldFiles
     *
     * @param array $files
     */
    public function testRemoveFile(array $files)
    {
        $that = $this;
        $root = $this->root.'bar/';
        $entity = $this->getMock('\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface');
        $entity
            ->expects($this->atLeastOnce())
            ->method('getFilename')
            ->will($this->returnValue('baz'));
        $entity
            ->expects($this->atLeastOnce())
            ->method('getDownloadPath')
            ->will($this->returnValue('bar'));
        $entity
            ->expects($this->once())
            ->method('getOldFilenames')
            ->will($this->returnValue($files));

        /* @var $args \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs */
        $args = $this->getMockBuilder('\Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->atLeastOnce())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->fs
            ->expects($this->atLeastOnce())
            ->method('remove')
            ->will($this->returnCallback(function ($file) use ($files, $root, $that) {
                $filename = pathinfo($file, PATHINFO_BASENAME);
                if ($filename == 'baz') { // origin file
                    $that->assertEquals($root.'baz', $file);
                } else { // old files
                    $that->assertContains($filename, $files);
                    $that->assertEquals($root.$filename, $file);
                }
            }));

        // test
        $this->listener->postRemove($args);
    }
}
