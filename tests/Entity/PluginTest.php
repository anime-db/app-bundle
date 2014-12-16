<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Entity;

use AnimeDb\Bundle\AppBundle\Entity\Plugin;

/**
 * Test installed plugin
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Plugin
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Plugin
     */
    protected $plugin;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->plugin = new Plugin();
    }

    /**
     * Get logo setters
     *
     * @return array
     */
    public function getLogoSetters()
    {
        return [
            ['setLogo'],
            ['setFilename']
        ];
    }

    /**
     * Test logo
     *
     * @dataProvider getLogoSetters
     *
     * @param string $method
     */
    public function testLogo($method)
    {
        $this->assertEmpty($this->plugin->getLogo());
        $this->assertEmpty($this->plugin->getFilename());
        $this->assertEmpty($this->plugin->getOldFilenames());

        call_user_func([$this->plugin, $method], 'foo');
        $this->assertEquals('foo', $this->plugin->getLogo());
        $this->assertEquals('foo', $this->plugin->getFilename());
        $this->assertEmpty($this->plugin->getOldFilenames());

        call_user_func([$this->plugin, $method], 'bar');
        $this->assertEquals('bar', $this->plugin->getLogo());
        $this->assertEquals('bar', $this->plugin->getFilename());
        $this->assertEquals(['foo'], $this->plugin->getOldFilenames());
    }

    /**
     * Test get download path
     */
    public function testGetDownloadPath()
    {
        $this->assertEquals('media/plugin/', $this->plugin->getDownloadPath());

        $this->plugin->setName('foo/bar');
        $this->assertEquals('media/plugin/foo/bar', $this->plugin->getDownloadPath());
    }

    /**
     * Test get web path
     */
    public function testGetWebPath()
    {
        $this->assertEmpty($this->plugin->getWebPath());

        $this->plugin->setLogo('foo');
        $this->assertEquals('/media/plugin//foo', $this->plugin->getWebPath());

        $this->plugin->setName('foo/bar');
        $this->assertEquals('/media/plugin/foo/bar/foo', $this->plugin->getWebPath());
    }
}