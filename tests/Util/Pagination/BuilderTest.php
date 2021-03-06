<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Util\Pagination;

use AnimeDb\Bundle\AppBundle\Util\Pagination\Builder;

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
     * @param int $max_navigate
     * @param int $total_pages
     * @param int $current_page
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
