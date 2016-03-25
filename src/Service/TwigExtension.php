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

/**
 * Twig extension
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var FragmentHandler
     */
    private $handler;

    /**
     * @var WidgetsContainer
     */
    private $widgets;

    /**
     * @param Router $router
     * @param FragmentHandler $handler
     * @param WidgetsContainer $widgets
     */
    public function __construct(Router $router, FragmentHandler $handler, WidgetsContainer $widgets)
    {
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
     * @param string $url
     *
     * @return string|false
     */
    public function favicon($url)
    {
        return $url ? $this->router->generate('media_favicon', ['host' => parse_url($url, PHP_URL_HOST)]) : false;
    }

    /**
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
