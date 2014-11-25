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

use AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pagination view
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination
 * @author Peter Gribanov <info@peter-gribanov.ru>
 */
class View implements \IteratorAggregate
{
    /**
     * Configuration
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration
     */
    protected $config;

    /**
     * First page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node|null
     */
    protected $first = null;

    /**
     * Previous page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node|null
     */
    protected $prev = null;

    /**
     * Current page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    protected $current;

    /**
     * Next page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node|null
     */
    protected $next = null;

    /**
     * Last page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node|null
     */
    protected $last = null;

    /**
     * List page nodes
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|null
     */
    protected $list = null;

    /**
     * Construct
     *
     * @param \AnimeDb\Bundle\AppBundle\Util\Pagination\Configuration $config
     *
     * @return array
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
    }

    /**
     * Get total pages
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->config->getTotalPages();
    }

    /**
     * Get first page node
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node|null
     */
    public function getFirst()
    {
        if (!$this->first && $this->config->getCurrentPages() > 1) {
            $this->first = new Node(1, $this->buildLink(1));
        }
        return $this->first;
    }

    /**
     * Get previous page node
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Prev|null
     */
    public function getPrev()
    {
        if (!$this->prev && $this->config->getCurrentPages() > 1) {
            $this->prev = new Node(
                $this->config->getCurrentPages() - 1,
                $this->buildLink($this->config->getCurrentPages() - 1)
            );
        }
        return $this->prev;
    }

    /**
     * Get current page node
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current
     */
    public function getCurrent()
    {
        if (!$this->current) {
            $this->current = new Node(
                $this->config->getCurrentPages(),
                $this->buildLink($this->config->getCurrentPages()),
                true
            );
        }
        return $this->current;
    }

    /**
     * Get next page node
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next|null
     */
    public function getNext()
    {
        if (!$this->next && $this->config->getCurrentPages() < $this->getTotal()) {
            $this->next = new Node(
                $this->config->getCurrentPages() + 1,
                $this->buildLink($this->config->getCurrentPages() + 1)
            );
        }
        return $this->next;
    }

    /**
     * Get last page node
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last|null
     */
    public function getLast()
    {
        if (!$this->last && $this->config->getCurrentPages() < $this->getTotal()) {
            $this->last = new Node($this->getTotal(), $this->buildLink($this->getTotal()));
        }
        return $this->last;
    }

    /**
     * (non-PHPdoc)
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        if (!($this->last instanceof ArrayCollection)) {
            $this->list = new ArrayCollection();

            if ($this->getTotal() <= 1) {
                return $this->list;
            }

            // definition of offset to the left and to the right of the selected page
            $left_offset = floor(($this->config->getMaxNavigate() - 1) / 2);
            $right_offset = ceil(($this->config->getMaxNavigate() - 1) / 2);
            // adjustment, if the offset is too large left
            if ($this->config->getCurrentPages() - $left_offset < 1) {
                $offset = abs($this->config->getCurrentPages() - 1 - $left_offset);
                $left_offset = $left_offset - $offset;
                $right_offset = $right_offset + $offset;
            }
            // adjustment, if the offset is too large right
            if ($this->config->getCurrentPages() + $right_offset > $this->getTotal()) {
                $offset = abs($this->getTotal() - $this->config->getCurrentPages() - $right_offset);
                $left_offset = $left_offset + $offset;
                $right_offset = $right_offset - $offset;
            }
            // determining the first and last pages in paging based on the current page and offset
            $page_from = $this->config->getCurrentPages() - $left_offset;
            $page_to = $this->config->getCurrentPages() + $right_offset;
            $page_from = $page_from > 1 ? $page_from : 1;

            // build list
            for ($page = $page_from; $page <= $page_to; $page++) {
                if ($page == $this->config->getCurrentPages()) {
                    $this->list->add($this->getCurrent());
                } else {
                    $this->list->add(new Node($page, $this->buildLink($page)));
                }
            }
        }

        return $this->list;
    }

    /**
     * Build link
     *
     * @param integer $page
     *
     * @return string
     */
    protected function buildLink($page)
    {
        if ($page == 1 && $this->config->getFerstPageLink()) {
            return $this->config->getFerstPageLink();
        }

        if (is_callable($this->config->getPageLink())) {
            return call_user_func($this->config->getPageLink(), $page);
        } else {
            return sprintf($this->config->getPageLink(), $page);
        }
    }
}
