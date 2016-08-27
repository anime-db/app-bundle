<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Event\Widget;

/**
 * Widget event names
 */
final class StoreEvents
{
    /**
     * Event thrown when a widgets container get a list of widgets for place
     *
     * @var string
     */
    const GET = 'anime_db.widget.get';
}
