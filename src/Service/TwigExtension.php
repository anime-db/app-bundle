<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use AnimeDb\Bundle\AppBundle\Service\WidgetsContainer;

/**
 * Twig extension
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * Router
     *
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $router;

    /**
     * Handler
     *
     * @var \Symfony\Component\HttpKernel\Fragment\FragmentHandler
     */
    private $handler;

    /**
     * Widget container
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\WidgetsContainer
     */
    private $widgets;

    /**
     * Construct
     *
     * @param \Symfony\Bundle\FrameworkBundle\Routing\Router $router
     * @param \Symfony\Component\HttpKernel\Fragment\FragmentHandler $handler
     * @param \AnimeDb\Bundle\AppBundle\Service\WidgetsContainer $widgets
     */
    public function __construct(
        Router $router,
        FragmentHandler $handler,
        WidgetsContainer $widgets
    ) {
        $this->router = $router;
        $this->handler = $handler;
        $this->widgets = $widgets;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            'favicon' => new \Twig_SimpleFilter('favicon', [$this, 'favicon'])
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'widgets' => new \Twig_SimpleFunction('widgets', [$this, 'widgets'], ['is_safe' => ['html']])
        ];
    }

    /**
     * Favicon
     *
     * @param string $url
     *
     * @return string|false
     */
    public function favicon($url)
    {
        return $url ? $this->router->generate('media_favicon', ['host' => parse_url($url, PHP_URL_HOST)]) : false;
    }

    /**
     * Render widgets
     *
     * @param string $place
     * @param array $attributes
     * @param array $options
     *
     * @return string
     */
    public function widgets($place, array $attributes = [], array $options = [])
    {
        $result = '';
        foreach ($this->widgets->getWidgetsForPlace($place) as $controller) {
            $result .= $this->handler->render(
                new ControllerReference($controller, $attributes, []),
                'hinclude',
                $options
            );
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'anime_db_app_extension';
    }
}
