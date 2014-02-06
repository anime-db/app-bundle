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
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\Constraints\Locale;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Request listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Request
{
    /**
     * Session name for the locale
     *
     * @var string
     */
    const SESSION_LOCALE = '_locale';

    /**
     * Translatable listener
     *
     * @var \Gedmo\Translatable\TranslatableListener
     */
    protected $translatable;

    /**
     * Validator
     *
     * @var \Symfony\Component\Validator\Validator
     */
    private $validator;

    /**
     * Container
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * Construct
     *
     * @param \Gedmo\Translatable\TranslatableListener $translatable
     * @param \Symfony\Component\Validator\Validator $validator
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(
        TranslatableListener $translatable,
        Validator $validator,
        ContainerInterface $container
    ) {
        $this->translatable = $translatable;
        $this->validator = $validator;
        $this->container = $container;
    }

    /**
     * Kernel request handler
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
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
        // set locale from session
        if ($request->hasPreviousSession()) {
            $request->getSession()->set(self::SESSION_LOCALE, $locale);
        }
        $request->setLocale($locale);
        setlocale(LC_ALL, $locale);

        $locale = substr($locale, 0, 2);
        // update parameters
        if ($this->container->getParameter('locale') != $locale) {
            $file = $this->container->getParameter('kernel.root_dir').'/config/parameters.yml';
            $parameters = Yaml::parse($file);
            $parameters['parameters']['locale'] = $locale;
            file_put_contents($file, Yaml::dump($parameters));
            // clear cache
            $fs = new Filesystem();
            $fs->remove($this->root.'/cache/');
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
        // set locale from session
        if ($request->hasPreviousSession()) {
            if ($locale = $request->getSession()->get(self::SESSION_LOCALE)) {
                return $locale;
            }
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
}