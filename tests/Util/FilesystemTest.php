<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Util;

use AnimeDb\Bundle\AppBundle\Util\Filesystem;

/**
 * Test filesystem
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Filesystem component
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $component;

    /**
     * Filesystem
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Filesystem
     */
    protected $fs;

    /**
     * File
     *
     * @var string
     */
    protected $file;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->component = $this->getMock('\Symfony\Component\Filesystem\Filesystem');
        $this->fs = new Filesystem($this->component);
        $this->file = tempnam(sys_get_temp_dir(), 'test');
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        @unlink($this->file);
    }

    /**
     * Get file contents
     *
     * @return array
     */
    public function getFileContents()
    {
        return [
            ['', true],
            ['', false],
            [
                'iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAYAAACprHcmAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1h'.
                'Z2VSZWFkeXHJZTwAAAAkSURBVHjaYvz//z8DsYCJgQRAe8Ufgfg/AUxDZzAOwaADCDAADKwO+JOzCXsAAAAASUVORK5CYII=',
                true
            ],
            [
                'iVBORw0KGgoAAAANSUhEUgAAAAsAAAALCAYAAACprHcmAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1h'.
                'Z2VSZWFkeXHJZTwAAAAkSURBVHjaYvz//z8DsYCJgQRAe8Ufgfg/AUxDZzAOwaADCDAADKwO+JOzCXsAAAAASUVORK5CYII=',
                false
            ]
        ];
    }

    /**
     * Test download image
     *
     * @dataProvider getFileContents
     *
     * @param string $content
     * @param boolean $override
     */
    public function testDownloadImage($content, $override)
    {
        file_put_contents($this->file, $content);
        $this->component
            ->expects($this->once())
            ->method('copy')
            ->with('http://example.com', $this->file, $override);
        $this->fs->downloadImage('http://example.com', $this->file, $override);
    }
}
