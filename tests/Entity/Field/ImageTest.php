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

class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Image
     */
    protected $image;

    /**
     * @var UploadedFile
     */
    protected $file;

    protected function setUp()
    {
        $this->file = new UploadedFile(tempnam(sys_get_temp_dir(), 'foo'), 'bar');
        $this->image = new Image();
    }

    protected function tearDown()
    {
        unlink($this->file->getPathname());
    }

    public function testHasImage()
    {
        $this->assertFalse($this->image->hasImage());

        $this->image->setRemote('foo');
        $this->assertTrue($this->image->hasImage());
    }

    public function testHasImageLocal()
    {
        $this->image->setLocal($this->file);
        $this->assertTrue($this->image->hasImage());
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        $this->setUp();
        return [
            ['getLocal', 'setLocal', null, $this->file],
            ['getRemote', 'setRemote', '', 'http://example.com/foo'],
            ['getFilename', 'setFilename', '', 'foo', 'tmp/'.date('Ymd').'/foo'],
            ['getFilename', 'setFilename', '', 'tmp/20141018/bar']
        ];
    }

    /**
     * @dataProvider getMethods
     *
     * @param callback $getter
     * @param callback $setter
     * @param mixed $default
     * @param mixed $new
     * @param mixed $expected
     */
    public function testGetSet($getter, $setter, $default, $new, $expected = null)
    {
        $expected = !is_null($expected) ? $expected : $new;
        $this->assertEquals($default, call_user_func([$this->image, $getter]));

        call_user_func([$this->image, $setter], $new);
        $this->assertEquals($expected, call_user_func([$this->image, $getter]));
    }

    public function testClear()
    {
        $this->image->setLocal($this->file);
        $this->image->setRemote('http://example.com/foo');
        $this->image->clear();
        $this->assertEmpty($this->image->getLocal());
        $this->assertEmpty($this->image->getRemote());
    }
}
