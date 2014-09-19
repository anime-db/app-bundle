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

/**
 * Test entity listener
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Filesystem
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fs;

    /**
     * Download root dir
     *
     * @var string
     */
    protected $root = '/foo/';

    /**
     * Entity listener
     *
     * @var \AnimeDb\Bundle\AppBundle\Event\Listener\Entity
     */
    protected $listener;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->fs = $this->getMock('\Symfony\Component\Filesystem\Filesystem');
        $this->listener = new Entity($this->fs, $this->root);
    }

    /**
     * Get methods
     *
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
     * Test ignore entity
     *
     * @dataProvider getMethods
     *
     * @param string $method
     */
    public function testIgnoreEntity($method)
    {
        $args = $this->getMockBuilder('\Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn(new \stdClass());
        $this->fs
            ->expects($this->never())
            ->method('remove');
        // test
        call_user_func([$this->listener, $method], $args);
    }

    /**
     * Get methods and old files
     *
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
     * Test remove old files
     *
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
            ->willReturn('');
        $entity
            ->expects($this->once())
            ->method('getDownloadPath')
            ->willReturn('bar');
        $entity
            ->expects($this->once())
            ->method('getOldFilenames')
            ->willReturn($files);

        $args = $this->getMockBuilder('\Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->atLeastOnce())
            ->method('getEntity')
            ->willReturn($entity);
        $this->fs
            ->expects($files ? $this->atLeastOnce() : $this->never())
            ->method('remove')
            ->willReturnCallback(function ($file) use ($files, $root, $that) {
                $filename = pathinfo($file, PATHINFO_BASENAME);
                $that->assertContains($filename, $files);
                $that->assertEquals($root.$filename, $file);
            });
        // test
        call_user_func([$this->listener, $method], $args);
    }

    /**
     * Get old files
     *
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
     * Test post remove
     *
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
            ->willReturn('baz');
        $entity
            ->expects($this->atLeastOnce())
            ->method('getDownloadPath')
            ->willReturn('bar');
        $entity
            ->expects($this->once())
            ->method('getOldFilenames')
            ->willReturn($files);

        $args = $this->getMockBuilder('\Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->atLeastOnce())
            ->method('getEntity')
            ->willReturn($entity);
        $this->fs
            ->expects($this->atLeastOnce())
            ->method('remove')
            ->willReturnCallback(function ($file) use ($files, $root, $that) {
                $filename = pathinfo($file, PATHINFO_BASENAME);
                if ($filename == 'baz') { // origin file
                    $that->assertEquals($root.'baz', $file);
                } else { // old files
                    $that->assertContains($filename, $files);
                    $that->assertEquals($root.$filename, $file);
                }
            });
        // test
        $this->listener->postRemove($args);
    }
}