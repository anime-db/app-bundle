<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Service\Downloader\Entity;

/**
 * Test base entity
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Service\Downloader\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class BaseEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Base entity
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entity;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->entity = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\BaseEntity')
            ->getMockForAbstractClass();
    }

    /**
     * Test filename
     */
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

    /**
     * Test get download path
     */
    public function testGetDownloadPath()
    {
        $entity = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\BaseEntity')
            ->getMockForAbstractClass();
        $this->assertEquals('media', $this->entity->getDownloadPath());
    }

    /**
     * Test get web path
     */
    public function testGetWebPath()
    {
        $this->assertEmpty($this->entity->getWebPath());
        $this->entity->setFilename('foo');
        $this->assertEquals('/media/foo', $this->entity->getWebPath());
    }
}