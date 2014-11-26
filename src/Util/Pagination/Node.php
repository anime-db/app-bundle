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

use AnimeDb\Bundle\AppBundle\Util\Pagination;

/**
 * Pagination node
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Node
{
    /**
     * Page number
     *
     * @var integer
     */
    protected $page = 1;

    /**
     * Link
     *
     * @var string
     */
    protected $link = '';

    /**
     * Is current page
     *
     * @var boolean
     */
    protected $is_current = false;

    /**
     * Construct
     *
     * @param integer $page
     * @param integer $link
     * @param boolean $is_current
     */
    public function __construct($page = 1, $link = '', $is_current = false)
    {
        $this->page = $page;
        $this->link = $link;
        $this->is_current = $is_current;
    }

    /**
     * Is current page
     *
     * @return boolean
     */
    public function isCurrent()
    {
        return $this->is_current;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Get page number
     *
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }
}
