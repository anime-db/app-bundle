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
use AnimeDb\Bundle\AppBundle\Service\CacheClearer;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters;

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
    protected $validator;

    /**
     * Cache clearer
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\CacheClearer
     */
    protected $cache_clearer;

    /**
     * Locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Parameters manipulator
     *
     * @var \AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters
     */
    protected $parameters;

    /**
     * Construct
     *
     * @param \Gedmo\Translatable\TranslatableListener $translatable
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \AnimeDb\Bundle\AppBundle\Service\CacheClearer $cache_clearer
     * @param \AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters $parameters
     * @param string $locale
     */
    public function __construct(
        TranslatableListener $translatable,
        ValidatorInterface $validator,
        CacheClearer $cache_clearer,
        Parameters $parameters,
        $locale
    ) {
        $this->translatable = $translatable;
        $this->validator = $validator;
        $this->cache_clearer = $cache_clearer;
        $this->parameters = $parameters;
        $this->locale = $locale;
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
        if ($this->locale != $locale) {
            $this->parameters->set('locale', $locale);
            $this->parameters->set('last_update', gmdate('r')); // TODO @deprecated
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
        if ($this->locale) {
            return $this->locale;
        }

        // get locale from language list
        $constraint = new Locale();
        foreach ($request->getLanguages() as $language) {
            if (!$this->validator->validate($language, $constraint)->has(0)) {
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
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            $event->getResponse()->setPublic();
            // cache must revalidate
            if ($event->getResponse()->getLastModified() && !$event->getResponse()->getMaxAge()) {
                $event->getResponse()->headers->addCacheControlDirective('must-revalidate', true);
            }
        }
    }
}
