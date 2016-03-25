<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Service;

use AnimeDb\Bundle\AppBundle\Service\PhpFinder;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Test php finder
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class PhpFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \AnimeDb\Bundle\AppBundle\Service\PhpFinder
     */
    protected $finder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PhpExecutableFinder
     */
    protected $driver;

    protected function setUp()
    {
        $this->driver = $this->getMock('\Symfony\Component\Process\PhpExecutableFinder');
        $this->finder = new PhpFinder($this->driver);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetPathFail()
    {
        $this->driver
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue(null));
        $this->finder->getPath();
    }

    public function testGetPath()
    {
        $expected = "'foo'";
        $this->driver
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue('foo'));

        $this->assertEquals($expected, $this->finder->getPath());
        // test lazy load
        $this->assertEquals($expected, $this->finder->getPath());
    }
}
