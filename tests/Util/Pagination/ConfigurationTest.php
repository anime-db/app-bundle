<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Util\Pagination;

use AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration;

/**
 * Test configuration
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Configuration
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    protected $config;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->config = new Configuration(150, 33);
    }

    /**
     * Get configs
     *
     * @return array
     */
    public function getConfigs()
    {
        return [
            [10, 1],
            [150, 33],
        ];
    }

    /**
     * Test create
     *
     * @dataProvider getConfigs
     *
     * @param integer $total_pages
     * @param integer $current_page
     */
    public function testConstruct($total_pages, $current_page)
    {
        $config = new Configuration($total_pages, $current_page);
        $this->assertEquals($total_pages, $config->getTotalPages());
        $this->assertEquals($current_page, $config->getCurrentPages());
    }

    /**
     * Test create
     *
     * @dataProvider getConfigs
     *
     * @param integer $total_pages
     * @param integer $current_page
     */
    public function testCreate($total_pages, $current_page)
    {
        $config = Configuration::create($total_pages, $current_page);
        $this->assertEquals($total_pages, $config->getTotalPages());
        $this->assertEquals($current_page, $config->getCurrentPages());
    }

    /**
     * Get methods
     *
     * @return array
     */
    public function getMethods()
    {
        return [
            [
                150,
                10,
                'getTotalPages',
                'setTotalPages'
            ],
            [
                33,
                1,
                'getCurrentPages',
                'setCurrentPages'
            ],
            [
                Configuration::DEFAULT_LIST_LENGTH,
                Configuration::DEFAULT_LIST_LENGTH + 5,
                'getMaxNavigate',
                'setMaxNavigate'
            ],
            [
                Configuration::DEFAULT_PAGE_LINK,
                'page_%s.html',
                'getPageLink',
                'setPageLink'
            ],
            [
                Configuration::DEFAULT_PAGE_LINK,
                function ($number) {
                    return 'page_'.$number.'.html';
                },
                'getPageLink',
                'setPageLink'
            ],
            [
                '',
                '/index.html',
                'getFerstPageLink',
                'setFerstPageLink'
            ],
        ];
    }

    /**
     * Test getters and setters
     *
     * @dataProvider getMethods
     *
     * @param mixed $default
     * @param mixed $new
     * @param string $getter
     * @param string $setter
     */
    public function testSetGet($default, $new, $getter, $setter)
    {
        $this->assertEquals($default, call_user_func([$this->config, $getter]));
        $this->assertEquals($this->config, call_user_func([$this->config, $setter], $new));
        $this->assertEquals($new, call_user_func([$this->config, $getter]));
    }

    /**
     * Test get view
     */
    public function testGetView()
    {
        $view = $this->config->getView();
        $this->assertInstanceOf('\AnimeDb\Bundle\AppBundle\Util\Pagination\View', $view);

        // test lazy load
        $this->config->setPageLink('?p=%s');
        $this->assertEquals($view, $this->config->getView());
    }
}
