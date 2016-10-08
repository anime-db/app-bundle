<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Service;

use AnimeDb\Bundle\AppBundle\Service\CacheClearer;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheClearerTest extends \PHPUnit_Framework_TestCase
{
    public function testClear()
    {
        $root = 'foo';
        /* @var $fs \PHPUnit_Framework_MockObject_MockObject|Filesystem */
        $fs = $this
            ->getMockBuilder('\Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();
        $fs
            ->expects($this->once())
            ->method('remove')
            ->with($root.'/cache/');

        $clearer = new CacheClearer($fs, $root);
        $clearer->clear();
    }

    public function testCatchException()
    {
        $root = 'foo';
        /* @var $fs \PHPUnit_Framework_MockObject_MockObject|Filesystem */
        $fs = $this
            ->getMockBuilder('\Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();
        $fs
            ->expects($this->once())
            ->method('remove')
            ->with($root.'/cache/')
            ->willThrowException(new IOException('bar'));

        $clearer = new CacheClearer($fs, $root);
        $clearer->clear();
    }
}
