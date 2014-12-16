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

use AnimeDb\Bundle\AppBundle\Util\Pagination\View;

/**
 * Pagination configuration
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination
 * @author Peter Gribanov <info@peter-gribanov.ru>
 */
class Configuration
{
    /**
     * Length of the list of pagination defaults
     *
     * @var integer
     */
    const DEFAULT_LIST_LENGTH = 5;

    /**
     * Default page link
     *
     * @var integer
     */
    const DEFAULT_PAGE_LINK = '%s';

    /**
     * Total number of pages
     *
     * @var integer
     */
    protected $total_pages = 0;

    /**
     * Current page
     *
     * @var integer
     */
    protected $current_page = 1;

    /**
     * View
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\View
     */
    protected $view;

    /**
     * The number of pages displayed in the navigation
     *
     * @var integer
     */
    protected $max_navigate = self::DEFAULT_LIST_LENGTH;

    /**
     * Page link
     *
     * @var string|callback
     */
    protected $page_link = self::DEFAULT_PAGE_LINK;

    /**
     * Link to the first page
     *
     * @var string
     */
    protected $ferst_page_link = '';

    /**
     * Construct
     *
     * @param integer $total_pages
     * @param integer $current_page
     */
    public function __construct($total_pages = 0, $current_page = 1)
    {
        $this->current_page = $current_page;
        $this->total_pages = $total_pages;
    }

    /**
     * Create configuration instance
     *
     * @param integer $total_pages
     * @param integer $current_page
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    public static function create($total_pages = 0, $current_page = 1)
    {
        return new static($total_pages, $current_page);
    }

    /**
     * Get total pages
     *
     * @return integer
     */
    public function getTotalPages()
    {
        return $this->total_pages;
    }

    /**
     * Set total pages
     *
     * @param integer $total_pages
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    public function setTotalPages($total_pages)
    {
        $this->total_pages = $total_pages;
        return $this;
    }

    /**
     * Get current pages
     *
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->current_page;
    }

    /**
     * Set current pages
     *
     * @param integer $current_page
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    public function setCurrentPage($current_page)
    {
        $this->current_page = $current_page;
        return $this;
    }

    /**
     * Get number of pages displayed in the navigation
     *
     * @return integer
     */
    public function getMaxNavigate()
    {
        return $this->max_navigate;
    }

    /**
     * Set number of pages displayed in the navigation
     *
     * @param integer $max_navigate
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    public function setMaxNavigate($max_navigate)
    {
        $this->max_navigate = $max_navigate;
        return $this;
    }

    /**
     * Get page link
     *
     * @return string|callback
     */
    public function getPageLink()
    {
        return $this->page_link;
    }

    /**
     * Set page link
     *
     * Basic reference, for example `page_%s.html` where %s page number, or
     * callback function which takes one parameter - the number of the page.
     *
     * <code>
     * function ($number) {
     *     return 'page_'.$number.'.html';
     * }
     * </code>
     *
     * @param string|callback $page_link
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    public function setPageLink($page_link)
    {
        $this->page_link = $page_link;
        return $this;
    }

    /**
     * Get ferst page link
     *
     * @return string
     */
    public function getFerstPageLink()
    {
        return $this->ferst_page_link;
    }

    /**
     * Set ferst page link
     *
     * @param string $ferst_page_link
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    public function setFerstPageLink($ferst_page_link)
    {
        $this->ferst_page_link = $ferst_page_link;
        return $this;
    }

    /**
     * Get view
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\View
     */
    public function getView()
    {
        if (!$this->view) {
            $this->view = new View($this);
        }
        return $this->view;
    }
}
