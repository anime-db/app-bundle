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
     * Get methods
     *
     * @return array
     */
    public function getMethods()
    {
        return [
            ['getName', 'setName'],
            ['getTitle', 'setTitle'],
            ['getDescription', 'setDescription'],
            ['getLogo', 'setLogo'],
            ['getDateInstall', 'setDateInstall', new \DateTime(), (new \DateTime())->modify('+100 seconds')]
        ];
    }

    /**
     * Test getters and setters
     *
     * @dataProvider getMethods
     * 
     * @param string $getter
     * @param string $setter
     * @param mixed $data
     */
    public function testGettersAndSetters($getter, $setter, $default = '', $new = 'foo')
    {
        $this->assertEquals($default, call_user_func([$this->plugin, $getter]));
        $this->assertEquals($this->plugin, call_user_func([$this->plugin, $setter], $new));
        $this->assertEquals($new, call_user_func([$this->plugin, $getter]));
    }

    /**
     * Test get upload root dir
     */
    public function testGetUploadRootDir()
    {
        $this->plugin->setName('foo');

        $this->assertEquals(
            str_replace('/tests/', '/src/', __DIR__).'/../../../../../web/media/plugin/foo',
            $this->plugin->getUploadRootDir()
        );
    }

    /**
     * Test get absolute path
     */
    public function testGetAbsolutePath()
    {
        $this->plugin->setName('foo');
        $this->plugin->setLogo('bar.jpg');

        $this->assertEquals(
            str_replace('/tests/', '/src/', __DIR__).'/../../../../../web/media/plugin/foo/bar.jpg',
            $this->plugin->getAbsolutePath()
        );
    }

    /**
     * Test get logo web path
     */
    public function testGetLogoWebPath()
    {
        $this->assertEmpty($this->plugin->getLogoWebPath());

        $this->plugin->setName('foo');
        $this->plugin->setLogo('bar.jpg');
        $this->assertEquals('/media/plugin/foo/bar.jpg', $this->plugin->getLogoWebPath());
    }

    /**
     * Test do remove logo
     */
    public function testDoRemoveLogo()
    {
        $this->plugin->doRemoveLogo();
    }
}