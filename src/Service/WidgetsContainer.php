<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use AnimeDb\Bundle\AppBundle\Event\Widget\StoreEvents;
use AnimeDb\Bundle\AppBundle\Event\Widget\Get;

class WidgetsContainer
{
    /**
     * @var array
     */
    private $widgets = [];

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $place
     *
     * @return array
     */
    public function getWidgetsForPlace($place)
    {
        // send the event to those who did not add widgets could do it
        $this->dispatcher->dispatch(StoreEvents::GET, new Get($this, $place));

        return isset($this->widgets[$place]) ? $this->widgets[$place] : [];
    }

    /**
     * Regist widget.
     *
     * Controller example:
     *   AcmeDemoBundle:Welcome:index
     *   AcmeArticleBundle:Article:show
     *
     * @param string $place
     * @param string $controller
     *
     * @return bool
     */
    public function registr($place, $controller)
    {
        if (preg_match('/^[a-z0-9]+:[a-z0-9]+:[_a-z0-9]+$/i', $controller)) {
            if (!isset($this->widgets[$place])) {
                $this->widgets[$place][] = $controller;
            } elseif (!in_array($controller, $this->widgets[$place])) {
                $this->widgets[$place][] = $controller;
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $place
     * @param string $controller
     *
     * @return bool
     */
    public function unregistr($place, $controller)
    {
        if (isset($this->widgets[$place]) &&
            ($key = array_search($controller, $this->widgets[$place])) !== false
        ) {
            unset($this->widgets[$place][$key]);

            return true;
        }

        return false;
    }
}
