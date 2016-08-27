<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Entity;

use AnimeDb\Bundle\AppBundle\Entity\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;

    protected function setUp()
    {
        $this->plugin = new Plugin();
    }

    /**
     * @return array
     */
    public function getLogoSetters()
    {
        return [
            ['setLogo'],
            ['setFilename'],
        ];
    }

    /**
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

    public function testGetDownloadPath()
    {
        $this->assertEquals('media/plugin/', $this->plugin->getDownloadPath());

        $this->plugin->setName('foo/bar');
        $this->assertEquals('media/plugin/foo/bar', $this->plugin->getDownloadPath());
    }

    public function testGetWebPath()
    {
        $this->assertEmpty($this->plugin->getWebPath());

        $this->plugin->setLogo('foo');
        $this->assertEquals('/media/plugin//foo', $this->plugin->getWebPath());

        $this->plugin->setName('foo/bar');
        $this->assertEquals('/media/plugin/foo/bar/foo', $this->plugin->getWebPath());
    }
}
