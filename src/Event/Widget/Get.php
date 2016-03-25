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

use Symfony\Component\EventDispatcher\Event;
use AnimeDb\Bundle\AppBundle\Service\WidgetsContainer;

/**
 * Event thrown when a widgets container get a list of widgets for place
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Widget
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Get extends Event
{
    /**
     * @var WidgetsContainer
     */
    protected $container;

    /**
     * @var string
     */
    protected $place;

    /**
     * @param WidgetsContainer $container
     * @param string $place
     */
    public function __construct(WidgetsContainer $container, $place)
    {
        $this->container = $container;
        $this->place = $place;
    }

    /**
     * @return WidgetsContainer
     */
    public function getWidgetsContainer()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Regist widget
     *
     * Controller example:
     *   AcmeDemoBundle:Welcome:index
     *   AcmeArticleBundle:Article:show
     *
     * @param string $controller
     *
     * @return bool
     */
    public function registr($controller)
    {
        return $this->container->registr($this->place, $controller);
    }

    /**
     * @param string $controller
     *
     * @return bool
     */
    public function unregistr($controller)
    {
        return $this->container->unregistr($this->place, $controller);
    }
}
