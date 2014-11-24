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
 * Pagination navigation
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination
 * @author Peter Gribanov <info@peter-gribanov.ru>
 */
class Navigation
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
     * Construct
     *
     * @param integer         $total_page      Total number of pages
     * @param integer         $current_page    Current page
     * @param integer         $max_navigate    The number of pages displayed in the navigation
     * @param string|callback $page_link       Basic reference, for example page_%s.html where %s page number,
     *                                          or circuit which takes one parameter - the number of the page
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
        // TODO init navigation
    }
}
