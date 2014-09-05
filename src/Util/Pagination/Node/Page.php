<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Util\Pagination\Node;

use AnimeDb\Bundle\AppBundle\Util\Pagination;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node;

/**
 * Node for other pages
 *
 * @package AnimeDb\Bundle\AppBundle\Util\Pagination\Node
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Page extends Node
{
    /**
     * Node title
     *
     * @var string
     */
    protected $title = 'Go to page number: %page%';

    /**
     * Page number
     *
     * @var string
     */
    protected $type = Pagination::TYPE_PAGE;
}
