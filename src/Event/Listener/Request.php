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
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Locale;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class Request
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TranslatableListener
     */
    protected $translatable;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param TranslatableListener $translatable
     * @param TranslatorInterface $translator
     * @param ValidatorInterface $validator
     * @param string $locale
     */
    public function __construct(
        TranslatableListener $translatable,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        $locale
    ) {
        $this->translatable = $translatable;
        $this->translator = $translator;
        $this->validator = $validator;
        $this->locale = $locale;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        /* @var $request HttpRequest */
        $request = $event->getRequest();

        // set default locale from request
        if ($request_locale = $request->getPreferredLanguage()) {
            $request->setDefaultLocale($request_locale);
        }

        // reset locale
        $this->setLocale($request, $this->getLocale($request));
    }

    /**
     * @param HttpRequest $request
     * @param string $locale
     */
    public function setLocale(HttpRequest $request, $locale)
    {
        setlocale(LC_ALL, $locale);
        $locale = substr($locale, 0, 2);
        $request->setLocale($locale);
        $this->translator->setLocale($locale);
        $this->translatable->setTranslatableLocale($locale);
    }

    /**
     * @param HttpRequest $request
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
     * @param FilterResponseEvent $event
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
