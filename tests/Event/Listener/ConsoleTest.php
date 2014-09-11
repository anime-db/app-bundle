<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Event\Listener;

use AnimeDb\Bundle\AppBundle\Event\Listener\Console;

/**
 * Test listener console
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class ConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get locales
     *
     * @return array
     */
    public function getLocales()
    {
        return [
            ['', Console::DEFAULT_LOCALE],
            ['ru'],
            ['en_US']
        ];
    }

    /**
     * Test on console command
     *
     * @dataProvider getLocales
     *
     * @param string $locale
     * @param string $expected
     */
    public function testOnConsoleCommand($locale, $expected = '')
    {
        $expected = $expected ?: $locale;

        $translatable = $this->getMockBuilder('\Gedmo\Translatable\TranslatableListener')
            ->disableOriginalConstructor()
            ->getMock();
        $translatable
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with(substr($expected, 0, 2));
        
        $translator = $this->getMockBuilder('\Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($expected);

        $listener = new Console($translatable, $translator, $locale);
        $listener->onConsoleCommand();
    }
}
