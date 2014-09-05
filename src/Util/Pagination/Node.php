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
     * Link
     *
     * @var string
     */
    protected $link = '';

    /**
     * Name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Is current page
     *
     * @var boolean
     */
    protected $is_current = false;

    /**
     * Node title
     *
     * @var string
     */
    protected $title = '';

    /**
     * Page number
     *
     * @var integer
     */
    protected $page = 1;

    /**
     * Page type
     *
     * @var string
     */
    protected $type = Pagination::TYPE_PAGE;

    /**
     * Set current page
     *
     * @param boolean $current
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    public function setCurrent($current) {
        $this->is_current = $current;
        return $this;
    }

    /**
     * Set link
     *
     * @param string $link
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    public function setLink($link) {
        $this->link = $link;
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Set page number
     *
     * @param integer $page
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    public function setPage($page) {
        $this->page = $page;
        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Get node title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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

    /**
     * Get page type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
