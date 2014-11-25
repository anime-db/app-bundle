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

use Doctrine\Common\Collections\ArrayCollection;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Page;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Prev;

/**
 * Pagination view
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination
 * @author Peter Gribanov <info@peter-gribanov.ru>
 */
class View implements \IteratorAggregate
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
     * Type for first page
     *
     * @var string
     */
    const TYPE_FIRST = 'first';

    /**
     * Type for previous page
     *
     * @var string
     */
    const TYPE_PREV = 'prev';

    /**
     * Type for other pages
     *
     * @var string
     */
    const TYPE_PAGE = 'page';

    /**
     * Type for next page
     *
     * @var string
     */
    const TYPE_NEXT = 'next';

    /**
     * Type for last page
     *
     * @var string
     */
    const TYPE_LAST = 'last';

    /**
     * Type for current page
     *
     * @var string
     */
    const TYPE_CURENT = 'current';

    /**
     * Types list
     *
     * @var array
     */
    public static $types_list = [
        self::TYPE_CURENT,
        self::TYPE_FIRST,
        self::TYPE_LAST,
        self::TYPE_NEXT,
        self::TYPE_PAGE,
        self::TYPE_PREV,
    ];

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
     * First page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First|null
     */
    protected $first = null;

    /**
     * Previous page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Prev|null
     */
    protected $prev = null;

    /**
     * Current page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current
     */
    protected $current;

    /**
     * Next page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next|null
     */
    protected $next = null;

    /**
     * Last page node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last|null
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
     * @param integer         $total_page      Total number of pages
     * @param integer         $current_page    Current page
     * @param integer         $max_navigate    The number of pages displayed in the navigation
     * @param string|callback $page_link       Basic reference, for example page_%s.html where %s page number, or
     *                                          callback function which takes one parameter - the number of the page
     * @param string          $ferst_page_link Link to the first page
     *
     * @return array
     */
    public function __construct(
        $total_page = 0,
        $current_page = 1,
        $max_navigate = self::DEFAULT_LIST_LENGTH,
        $page_link = self::DEFAULT_PAGE_LINK,
        $ferst_page_link = ''
    ) {
        $this->total_pages = $total_page;
        $this->current_page = $current_page;
        $this->max_navigate = $max_navigate;
        $this->page_link = $page_link;
        $this->ferst_page_link = $ferst_page_link;
    }

    /**
     * Get total pages
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->total_pages;
    }

    /**
     * Get first page node
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First|null
     */
    public function getFirst()
    {
        if (!$this->first && $this->current_page > 1) {
            $this->first = (new First())
                ->setLink($this->buildLink(1));
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
        if (!$this->prev && $this->current_page > 1) {
            $this->prev = (new Prev())
                ->setLink($this->buildLink($this->current_page - 1))
                ->setPage($this->current_page - 1);
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
            $this->current = (new Current())
                ->setLink($this->buildLink($this->current_page));
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
        if (!$this->next && $this->current_page < $this->total_pages) {
            $this->next = (new Next())
                ->setLink($this->buildLink($this->current_page + 1))
                ->setPage($this->current_page + 1);
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
        if (!$this->last && $this->current_page < $this->total_pages) {
            $this->last = (new Last())
                ->setLink($this->buildLink($this->total_pages))
                ->setPage($this->total_pages);
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

            if ($this->total_pages <= 1) {
                return $this->list;
            }

            // definition of offset to the left and to the right of the selected page
            $left_offset = floor(($this->max_navigate - 1) / 2);
            $right_offset = ceil(($this->max_navigate - 1) / 2);
            // adjustment, if the offset is too large left
            if ($this->current_page - $left_offset < 1) {
                $offset = abs($this->current_page - 1 - $left_offset);
                $left_offset = $left_offset - $offset;
                $right_offset = $right_offset + $offset;
            }
            // adjustment, if the offset is too large right
            if ($this->current_page + $right_offset > $this->total_pages) {
                $offset = abs($this->total_pages - $this->current_page - $right_offset);
                $left_offset = $left_offset + $offset;
                $right_offset = $right_offset - $offset;
            }
            // determining the first and last pages in paging based on the current page and offset
            $page_from = $this->current_page - $left_offset;
            $page_to = $this->current_page + $right_offset;
            $page_from = $page_from > 1 ? $page_from : 1;

            // build list
            for ($page = $page_from; $page <= $page_to; $page++) {
                if ($page == $this->current_page) {
                    $this->list->add($this->getCurrent());
                } else {
                    $this->list->add(
                        (new Page())
                            ->setLink($this->buildLink($page))
                            ->setPage($page)
                            ->setName($page)
                    );
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
        if ($page == 1 && $this->ferst_page_link) {
            return $this->ferst_page_link;
        }

        if (is_callable($this->page_link)) {
            return call_user_func($this->page_link, $page);
        } else {
            return sprintf($this->page_link, $page);
        }
    }
}
