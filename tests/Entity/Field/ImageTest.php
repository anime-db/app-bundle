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
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->image = new Image();
    }

    /**
     * Test get path
     */
    public function testGetPath()
    {
        $this->assertEmpty($this->image->getPath());
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
        $file = $this->getMockBuilder('\Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock();
        $this->image->setLocal($file);

        $this->assertTrue($this->image->isSetImage());
    }
}