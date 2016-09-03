<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Service\Downloader\Entity;

use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\BaseEntity;

class BaseEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BaseEntity
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = $this
            ->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\BaseEntity')
            ->getMockForAbstractClass();
    }

    public function testFilename()
    {
        // empty
        $this->assertEmpty($this->entity->getFilename());
        $this->assertEmpty($this->entity->getOldFilenames());
        // set foo
        $this->entity->setFilename('foo');
        $this->assertEquals('foo', $this->entity->getFilename());
        // set bar and mark foo as old
        $this->entity->setFilename('bar');
        $this->assertEquals('bar', $this->entity->getFilename());
        $this->assertEquals(['foo'], $this->entity->getOldFilenames());
    }

    public function testGetDownloadPath()
    {
        $this->assertEquals('media', $this->entity->getDownloadPath());
    }

    public function testGetWebPath()
    {
        $this->assertEmpty($this->entity->getWebPath());
        $this->entity->setFilename('foo');
        $this->assertEquals('/media/foo', $this->entity->getWebPath());
    }
}
