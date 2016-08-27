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

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Translation\TranslatorInterface;

class Console
{
    /**
     * @var string
     */
    const DEFAULT_LOCALE = 'en';

    /**
     * @var TranslatableListener
     */
    protected $translatable;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param TranslatableListener $translatable
     * @param TranslatorInterface $translator
     * @param string $locale
     */
    public function __construct(TranslatableListener $translatable, TranslatorInterface $translator, $locale = '')
    {
        $this->translatable = $translatable;
        $this->translator = $translator;
        $this->locale = $locale ?: self::DEFAULT_LOCALE;
    }

    public function onConsoleCommand()
    {
        setlocale(LC_ALL, $this->locale);
        $this->translator->setLocale($this->locale);
        $this->translatable->setTranslatableLocale(substr($this->locale, 0, 2));
    }
}
