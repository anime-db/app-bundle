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
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Console listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Console
{
    /**
     * Translatable listener
     *
     * @var \Gedmo\Translatable\TranslatableListener
     */
    protected $translatable;

    /**
     * Translator
     *
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected $translator;

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
     * @param \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
     * @param string $locale
     */
    public function __construct(TranslatableListener $translatable, Translator $translator, $locale)
    {
        $this->translatable = $translatable;
        $this->translator = $translator;
        $this->locale = $locale ?: 'en';
    }

    /**
     * Kernel request handler
     */
    public function onConsoleCommand()
    {
        setlocale(LC_ALL, $this->locale);
        $this->translator->setLocale($this->locale);
        $this->translatable->setTranslatableLocale(substr($this->locale, 0, 2));
    }
}
