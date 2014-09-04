<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Entity\Field;

use AnimeDb\Bundle\AppBundle\Entity\Field\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Test item image
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Entity\Field
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Image
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Field\Image
     */
    protected $image;

    /**
     * Uploaded file
     *
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected $file;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->file = new UploadedFile(tempnam(sys_get_temp_dir(), 'foo'), 'bar');
        $this->image = new Image();
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->file->getPathname());
    }

    /**
     * Test get path
     */
    public function testGetPath()
    {
        $this->assertEmpty($this->image->getPath());
    }

    /**
     * Test set and get local file
     */
    public function testLocal()
    {
        $this->assertNull($this->image->getLocal());

        $this->image->setLocal($this->file);
        $this->assertEquals($this->file, $this->image->getLocal());
    }

    /**
     * Test is set image
     */
    public function testIsSetImage()
    {
        $this->assertFalse($this->image->isSetImage());

        $this->image->setRemote('foo');
        $this->assertTrue($this->image->isSetImage());
    }

    /**
     * Test is set image from local file
     */
    public function testIsSetImageLocal()
    {
        $this->image->setLocal($this->file);

        $this->assertTrue($this->image->isSetImage());
    }
}