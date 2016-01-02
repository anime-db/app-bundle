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

use AnimeDb\Bundle\AppBundle\Event\Listener\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test listener request
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Translator
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * Translatable
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translatable;

    /**
     * Validator
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * Request listener
     *
     * @var \AnimeDb\Bundle\AppBundle\Event\Listener\Request
     */
    protected $listener;

    /**
     * Locale
     *
     * @var string
     */
    protected $locale = 'en';

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('\Symfony\Component\Translation\TranslatorInterface');
        $this->translatable = $this->getMockBuilder('\Gedmo\Translatable\TranslatableListener')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = $this->getMock('\Symfony\Component\Validator\Validator\ValidatorInterface');

        $this->listener = new Request(
            $this->translatable,
            $this->translator,
            $this->validator,
            $this->locale
        );
    }

    /**
     * Test on kernel request ignore
     */
    public function testOnKernelRequestIgnore()
    {
        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::SUB_REQUEST);
        $event
            ->expects($this->never())
            ->method('getRequest');

        $this->listener->onKernelRequest($event);
    }

    /**
     * Get preferred languages
     *
     * @return array
     */
    public function getPreferredLanguages()
    {
        return [
            ['ru'],
            ['en'],
            [null]
        ];
    }

    /**
     * Test on kernel request
     *
     * @dataProvider getPreferredLanguages
     *
     * @param string $language
     */
    public function testOnKernelRequest($language)
    {
        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->willReturn($language);
        if ($language) {
            $request
                ->expects($this->once())
                ->method('setDefaultLocale')
                ->willReturn($language);
        } else {
            $request
                ->expects($this->never())
                ->method('setDefaultLocale');
        }
        $request
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->locale);
        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($event);
    }

    /**
     * Get locales
     *
     * @return array
     */
    public function getLocales()
    {
        return [
            ['ru'],
            ['en_US'],
            [$this->locale]
        ];
    }

    /**
     * Test set locale
     *
     * @dataProvider getLocales
     *
     * @param string $locale
     */
    public function testSetLocale($locale)
    {
        $expected = substr($locale, 0, 2);
        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects($this->once())
            ->method('setLocale')
            ->with($expected);
        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($expected);
        $this->translatable
            ->expects($this->once())
            ->method('setTranslatableLocale')
            ->with($expected);

        $this->listener->setLocale($request, $locale);
    }

    /**
     * Test get default locale
     */
    public function testGetLocaleDefault()
    {
        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($this->locale, $this->listener->getLocale($request));
    }

    /**
     * Get languages
     *
     * @return array
     */
    public function getLanguages()
    {
        return [
            [
                [],
                'en',
                'en'
            ],
            [
                ['ru'],
                'ru'
            ],
            [
                ['rus', 'fra', 'en'],
                'en'
            ],
            [
                ['rus', 'fra', 'en_US'],
                'en_US'
            ]
        ];
    }

    /**
     * Test get locale from request
     *
     * @dataProvider getLanguages
     *
     * @param array $languages
     * @param string $expected
     * @param string $locale
     */
    public function testGetLocaleFromRequest(array $languages, $expected, $locale = '')
    {
        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects($this->once())
            ->method('getLanguages')
            ->willReturn($languages);
        $request
            ->expects($locale ? $this->once() : $this->never())
            ->method('getLocale')
            ->willReturn($locale);

        // validate languages
        $that = $this;
        for ($i = 0; $i < count($languages); $i++) {
            $this->validator
                ->expects($this->at($i))
                ->method('validate')
                ->willReturnCallback(function ($language, $constraint) use ($that, $i, $languages, $locale) {
                    $that->assertEquals($languages[$i], $language);
                    $that->assertInstanceOf('\Symfony\Component\Validator\Constraints\Locale', $constraint);

                    $list = $that->getMock('\Symfony\Component\Validator\ConstraintViolationListInterface');
                    $list
                        ->expects($that->once())
                        ->method('has')
                        ->willReturn($i+1 < count($languages) || $locale)
                        ->with(0);
                    return $list;
                });
        }

        $listener = new Request(
            $this->translatable,
            $this->translator,
            $this->validator,
            ''
        );
        $this->assertEquals($expected, $listener->getLocale($request));
    }

    /**
     * Test on kernel response ignore
     */
    public function testOnKernelResponseIgnore()
    {
        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::SUB_REQUEST);
        $event
            ->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($event);
    }

    /**
     * Get responses
     *
     * @return array
     */
    public function getResponses()
    {
        return [
            [null, 0],
            [new \DateTime(), 3600],
            [new \DateTime(), 0]
        ];
    }

    /**
     * Test on kernel response
     *
     * @dataProvider getResponses
     *
     * @param \DateTime $last_modified
     * @param integer $max_age
     */
    public function testOnKernelResponse(\DateTime $last_modified = null, $max_age)
    {
        $response = $this->getMock('\Symfony\Component\HttpFoundation\Response');
        $response
            ->expects($this->once())
            ->method('setPublic');
        $response
            ->expects($this->once())
            ->method('setPublic');
        $response
            ->expects($this->once())
            ->method('getLastModified')
            ->willReturn($last_modified);
        $response
            ->expects($last_modified ? $this->once() : $this->never())
            ->method('getMaxAge')
            ->willReturn($max_age);

        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);
        $event
            ->expects($this->atLeastOnce())
            ->method('getResponse')
            ->willReturn($response);

        $response->headers = $this->getMock('\Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $response->headers
            ->expects($last_modified && !$max_age ? $this->once() : $this->never())
            ->method('addCacheControlDirective')
            ->with('must-revalidate', true);

        $this->listener->onKernelResponse($event);
    }
}
