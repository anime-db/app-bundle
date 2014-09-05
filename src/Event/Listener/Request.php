<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Event\Listener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Locale;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AnimeDb\Bundle\AppBundle\Service\CacheClearer;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Request listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Request
{
    /**
     * Translatable listener
     *
     * @var \Gedmo\Translatable\TranslatableListener
     */
    protected $translatable;

    /**
     * Validator
     *
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * Container
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * Cache clearer
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\CacheClearer
     */
    protected $cache_clearer;

    /**
     * Root dir
     *
     * @var string
     */
    protected $root;

    /**
     * Construct
     *
     * @param \Gedmo\Translatable\TranslatableListener $translatable
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \AnimeDb\Bundle\AppBundle\Service\CacheClearer $cache_clearer
     * @param string $root
     */
    public function __construct(
        TranslatableListener $translatable,
        ValidatorInterface $validator,
        ContainerInterface $container,
        CacheClearer $cache_clearer,
        $root
    ) {
        $this->translatable = $translatable;
        $this->validator = $validator;
        $this->container = $container;
        $this->cache_clearer = $cache_clearer;
        $this->root = $root;
    }

    /**
     * Kernel request handler
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        /* @var $request \Symfony\Component\HttpFoundation\Request */
        $request = $event->getRequest();

        // set default locale from request
        if ($request_locale = $request->getPreferredLanguage()) {
            $request->setDefaultLocale($request_locale);
        }

        // reset locale
        $this->setLocale($request, $this->getLocale($request));
    }

    /**
     * Set current locale
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $locale
     */
    public function setLocale(HttpRequest $request, $locale)
    {
        $request->setLocale($locale);
        setlocale(LC_ALL, $locale);

        $locale = substr($locale, 0, 2);
        // update parameters
        if ($this->container->getParameter('locale') != $locale) {
            $parameters = Yaml::parse($this->root.'/config/parameters.yml');
            $parameters['parameters']['locale'] = $locale;
            $parameters['parameters']['last_update'] = gmdate('r');
            file_put_contents($this->root.'/config/parameters.yml', Yaml::dump($parameters));
            // clear cache
            $this->cache_clearer->clear();
        }
        $this->translatable->setTranslatableLocale($locale);
    }

    /**
     * Get current locale
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string
     */
    public function getLocale(HttpRequest $request)
    {
        if ($this->container->getParameter('locale')) {
            return $this->container->getParameter('locale');
        }

        // get locale from language list
        $locale_constraint = new Locale();
        foreach ($request->getLanguages() as $language) {
            if (!count($this->validator->validateValue($language, $locale_constraint))) {
                return $language;
            }
        }

        // get default locale
        return $request->getLocale();
    }

    /**
     * Kernel response handler
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // cache response
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST &&
            $event->getResponse()->getLastModified() && !$event->getResponse()->getMaxAge()
        ) {
            $event->getResponse()->setPublic();
            $event->getResponse()->headers->addCacheControlDirective('must-revalidate', true);
        }
    }
}
