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

use AnimeDb\Bundle\AppBundle\Util\Pagination\Node;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Page;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Previous;

/**
 * Pagination
 *
 * @package AnimeDb\Bundle\AppBundle\Util
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Pagination
{
    /**
     * Length of the list of pagination defaults
     *
     * @var integer
     */
    const DEFAULT_LIST_LENGTH = 5;

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
     * Returns a list of navigation
     *
     * @param integer         $total           Total number of pages
     * @param integer         $current_page    Current page
     * @param integer         $max_navigate    The number of pages displayed in the navigation
     * @param string|\Closure $link            Basic reference, for example page_% s.html where% s page number,
     *                                          or circuit which takes one parameter - the number of the page
     * @param string|null     $ferst_page_link Link to the first page
     *
     * @return array
     */
    public function createNavigation(
        $total = 0,
        $current_page = 1,
        $max_navigate = self::DEFAULT_LIST_LENGTH,
        $link = '%s',
        $ferst_page_link = null
    ) {
        $current_page = (int)$current_page > 1 ? $current_page : 1;
        $result = [
            'total' => $total,
            'current' => $current_page,
            'max_navigate' => $max_navigate,
            'list' => []
        ];

        if ($total <= 1) {
            return $result;
        }

        // definition of offset to the left and to the right of the selected page
        $left_offset = floor(($max_navigate - 1) / 2);
        $right_offset = ceil(($max_navigate - 1) / 2);
        // adjustment, if the offset is too large left
        if ($current_page - $left_offset < 1) {
            $offset = abs($current_page - 1 - $left_offset);
            $left_offset = $left_offset - $offset;
            $right_offset = $right_offset + $offset;
        }
        // adjustment, if the offset is too large right
        if ($current_page + $right_offset > $total) {
            $offset = abs($total - $current_page - $right_offset);
            $left_offset = $left_offset + $offset;
            $right_offset = $right_offset - $offset;
        }
        // determining the first and last pages in paging based on the current page and offset
        $page_from = $current_page - $left_offset;
        $page_to = $current_page + $right_offset;
        $page_from = $page_from > 1 ? $page_from : 1;

        // first page
        if ($current_page != 1) {
            $result['list'][] = $this->getNode(self::TYPE_FIRST, $this->buildLink($link, 1, $ferst_page_link));
        }
        // previous page
        if ($current_page > 1) {
            $result['list'][] = $this->getNode(
                self::TYPE_PREV,
                $this->buildLink($link, ($current_page - 1), $ferst_page_link),
                $current_page - 1
            );
        }

        // pages list
        for ($page = $page_from; $page <= $page_to; $page++) {
            if ($page == $current_page) {
                $result['list'][] = $this->getNode(
                    self::TYPE_CURENT,
                    $this->buildLink($link, $current_page),
                    $current_page,
                    $current_page
                );
            } else {
                $result['list'][] = $this->getNode(
                    self::TYPE_PAGE,
                    $this->buildLink($link, $page, $ferst_page_link),
                    $page,
                    $page
                );
            }
        }

        // next page
        if ($current_page != $total) {
            $result['list'][] = $this->getNode(
                self::TYPE_NEXT,
                $this->buildLink($link, ($current_page + 1)),
                $current_page + 1
            );
        }

        // last page
        if ($current_page < $total) {
            $result['list'][] = $this->getNode(
                self::TYPE_LAST,
                $this->buildLink($link, $total),
                $total
            );
        }

        return $result;
    }

    /**
     * Build link
     *
     * @param string|\Closure $link            Basic reference, for example page_% s.html where% s page number,
     *                                          or circuit which takes one parameter - the number of the page
     * @param integer         $page            Page number
     * @param string|null     $ferst_page_link Link to the first page
     *
     * @return string
     */
    protected function buildLink($link, $page, $ferst_page_link = null) {
        if ($page == 1 && $ferst_page_link) {
            return $ferst_page_link;
        }

        if ($link instanceof \Closure) {
            return call_user_func($link, $page);
        } else {
            return sprintf($link, $page);
        }
    }

    /**
     * Get node by type
     *
     * @param string $type
     * @param string $link
     * @param integer $page
     * @param string $name
     *
     * @return \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    protected function getNode($type, $link = '', $page = 0, $name = '') {
        switch ($type) {
            case self::TYPE_CURENT:
                $node = new Current();
                break;
            case self::TYPE_FIRST:
                $node = new First();
                break;
            case self::TYPE_LAST:
                $node = new Last();
                break;
            case self::TYPE_NEXT:
                $node = new Next();
                break;
            case self::TYPE_PAGE:
                $node = new Page();
                break;
            case self::TYPE_PREV:
                $node = new Previous();
                break;
            default:
                $node = new Node();
        }
        if ($link) {
            $node->setLink($link);
        }
        if ($page) {
            $node->setPage($page);
        }
        if ($name) {
            $node->setName($name);
        }
        return $node;
    }
}
