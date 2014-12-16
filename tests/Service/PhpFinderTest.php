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

/**
 * Test php finder
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class PhpFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Finder
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\PhpFinder
     */
    protected $finder;

    /**
     * Driver
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $driver;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->driver = $this->getMock('\Symfony\Component\Process\PhpExecutableFinder');
        $this->finder = new PhpFinder($this->driver);
    }

    /**
     * Test get path fail
     *
     * @expectedException \RuntimeException
     */
    public function testGetPathFail()
    {
        $this->driver
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);
        $this->finder->getPath();
    }

    /**
     * Test get path
     */
    public function testGetPath()
    {
        $expected = "'foo'";
        $this->driver
            ->expects($this->once())
            ->method('find')
            ->willReturn('foo');

        $this->assertEquals($expected, $this->finder->getPath());
        // test lazy load
        $this->assertEquals($expected, $this->finder->getPath());
    }
}
