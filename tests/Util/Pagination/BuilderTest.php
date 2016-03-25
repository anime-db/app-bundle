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

use AnimeDb\Bundle\AppBundle\Util\Pagination\Builder;

/**
 * Test builder
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getConfigs()
    {
        return [
            [5, 10, 1],
            [10, 150, 33],
        ];
    }

    /**
     * @dataProvider getConfigs
     *
     * @param integer $max_navigate
     * @param integer $total_pages
     * @param integer $current_page
     */
    public function testCreate($max_navigate, $total_pages, $current_page)
    {
        $builder = new Builder($max_navigate);
        $config = $builder->create($total_pages, $current_page);
        $this->assertEquals($max_navigate, $config->getMaxNavigate());
        $this->assertEquals($total_pages, $config->getTotalPages());
        $this->assertEquals($current_page, $config->getCurrentPage());
    }
}
