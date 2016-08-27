<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Event\Listener;

use AnimeDb\Bundle\AppBundle\Event\Listener\Console;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Translation\TranslatorInterface;

class ConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getLocales()
    {
        return [
            ['', Console::DEFAULT_LOCALE],
            ['ru'],
            ['en_US'],
        ];
    }

    /**
     * @dataProvider getLocales
     *
     * @param string $locale
     * @param string $expected
     */
    public function testOnConsoleCommand($locale, $expected = '')
    {
        $expected = $expected ?: $locale;

        /* @var $translatable \PHPUnit_Framework_MockObject_MockObject|TranslatableListener */
        $translatable = $this
            ->getMockBuilder('\Gedmo\Translatable\TranslatableListener')
            ->disableOriginalConstructor()
            ->getMock();
        $translatable
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with(substr($expected, 0, 2));

        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMockBuilder('\Symfony\Component\Translation\TranslatorInterface')
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
