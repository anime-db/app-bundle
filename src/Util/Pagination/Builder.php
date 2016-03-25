<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Util\Pagination;

/**
 * Builder
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Builder
{
    /**
     * The number of pages displayed in the navigation
     *
     * @var integer
     */
    protected $max_navigate = Configuration::DEFAULT_LIST_LENGTH;

    /**
     * @param integer $max_navigate
     */
    public function __construct($max_navigate)
    {
        $this->max_navigate = $max_navigate;
    }

    /**
     * @param integer $total_pages
     * @param integer $current_page
     *
     * @return Configuration
     */
    public function create($total_pages = 0, $current_page = 1) {
        return (new Configuration($total_pages, $current_page))
            ->setMaxNavigate($this->max_navigate);
    }
}
