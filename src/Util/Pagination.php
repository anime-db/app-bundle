<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Util;

use AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration;

/**
 * Pagination
 *
 * @package AnimeDb\Bundle\AppBundle\Util
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Pagination
{
    /**
     * Create navigation
     *
     * @param integer $total_pages
     * @param integer $current_page
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Builder
     */
    public function create($total_pages = 0, $current_page = 1) {
        return new Configuration($total_pages, $current_page);
    }
}
