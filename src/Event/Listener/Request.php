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
    protected $validator;

    /**
     * Locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Construct
     *
     * @param \Gedmo\Translatable\TranslatableListener $translatable
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param string $locale
     */
    public function __construct(
        TranslatableListener $translatable,
        ValidatorInterface $validator,
        $locale
    ) {
        $this->translatable = $translatable;
        $this->validator = $validator;
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

        $this->translatable->setTranslatableLocale(substr($locale, 0, 2));
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
