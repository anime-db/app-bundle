<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Event\Listener;

use AnimeDb\Bundle\AppBundle\Event\Listener\Request;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatableListener
     */
    protected $translatable;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface
     */
    protected $validator;

    /**
     * @var Request
     */
    protected $listener;

    /**
     * @var string
     */
    protected $locale = 'en';

    protected function setUp()
    {
        $this->translator = $this->getMock('\Symfony\Component\Translation\TranslatorInterface');
        $this->translatable = $this
            ->getMockBuilder('\Gedmo\Translatable\TranslatableListener')
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

    public function testOnKernelRequestIgnore()
    {
        /* @var $event \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent */
        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::SUB_REQUEST));
        $event
            ->expects($this->never())
            ->method('getRequest');

        $this->listener->onKernelRequest($event);
    }

    /**
     * @return array
     */
    public function getPreferredLanguages()
    {
        return [
            ['ru'],
            ['en'],
            [null],
        ];
    }

    /**
     * @dataProvider getPreferredLanguages
     *
     * @param string $language
     */
    public function testOnKernelRequest($language)
    {
        $request = $this
            ->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->will($this->returnValue($language));
        if ($language) {
            $request
                ->expects($this->once())
                ->method('setDefaultLocale')
                ->will($this->returnValue($language));
        } else {
            $request
                ->expects($this->never())
                ->method('setDefaultLocale');
        }
        $request
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->locale);

        /* @var $event \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent */
        $event = $this
            ->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::MASTER_REQUEST));
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->listener->onKernelRequest($event);
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return [
            ['ru'],
            ['en_US'],
            [$this->locale],
        ];
    }

    /**
     * @dataProvider getLocales
     *
     * @param string $locale
     */
    public function testSetLocale($locale)
    {
        $expected = substr($locale, 0, 2);
        /* @var $request \PHPUnit_Framework_MockObject_MockObject|HttpRequest */
        $request = $this
            ->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
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

    public function testGetLocaleDefault()
    {
        /* @var $request \PHPUnit_Framework_MockObject_MockObject|HttpRequest */
        $request = $this
            ->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals($this->locale, $this->listener->getLocale($request));
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        return [
            [
                [],
                'en',
                'en',
            ],
            [
                ['ru'],
                'ru',
            ],
            [
                ['rus', 'fra', 'en'],
                'en',
            ],
            [
                ['rus', 'fra', 'en_US'],
                'en_US',
            ],
        ];
    }

    /**
     * @dataProvider getLanguages
     *
     * @param array $languages
     * @param string $expected
     * @param string $locale
     */
    public function testGetLocaleFromRequest(array $languages, $expected, $locale = '')
    {
        /* @var $request \PHPUnit_Framework_MockObject_MockObject|HttpRequest */
        $request = $this
            ->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request
            ->expects($this->once())
            ->method('getLanguages')
            ->will($this->returnValue($languages));
        $request
            ->expects($locale ? $this->once() : $this->never())
            ->method('getLocale')
            ->will($this->returnValue($locale));

        // validate languages
        $that = $this;
        for ($i = 0; $i < count($languages); ++$i) {
            $this->validator
                ->expects($this->at($i))
                ->method('validate')
                ->will($this->returnCallback(function ($language, $constraint) use ($that, $i, $languages, $locale) {
                    $that->assertEquals($languages[$i], $language);
                    $that->assertInstanceOf('\Symfony\Component\Validator\Constraints\Locale', $constraint);

                    $list = $that->getMock('\Symfony\Component\Validator\ConstraintViolationListInterface');
                    $list
                        ->expects($that->once())
                        ->method('has')
                        ->will($this->returnValue($i + 1 < count($languages) || $locale))
                        ->with(0);

                    return $list;
                }));
        }

        $listener = new Request(
            $this->translatable,
            $this->translator,
            $this->validator,
            ''
        );
        $this->assertEquals($expected, $listener->getLocale($request));
    }

    public function testOnKernelResponseIgnore()
    {
        /* @var $event \PHPUnit_Framework_MockObject_MockObject|FilterResponseEvent */
        $event = $this
            ->getMockBuilder('\Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::SUB_REQUEST));
        $event
            ->expects($this->never())
            ->method('getResponse');

        $this->listener->onKernelResponse($event);
    }

    /**
     * @return array
     */
    public function getResponses()
    {
        return [
            [null, 0],
            [new \DateTime(), 3600],
            [new \DateTime(), 0],
        ];
    }

    /**
     * @dataProvider getResponses
     *
     * @param \DateTime $last_modified
     * @param int $max_age
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
            ->will($this->returnValue($last_modified));
        $response
            ->expects($last_modified ? $this->once() : $this->never())
            ->method('getMaxAge')
            ->will($this->returnValue($max_age));

        /* @var $event \PHPUnit_Framework_MockObject_MockObject|FilterResponseEvent */
        $event = $this
            ->getMockBuilder('\Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->once())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::MASTER_REQUEST));
        $event
            ->expects($this->atLeastOnce())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $response->headers = $this->getMock('\Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $response->headers
            ->expects($last_modified && !$max_age ? $this->once() : $this->never())
            ->method('addCacheControlDirective')
            ->with('must-revalidate', true);

        $this->listener->onKernelResponse($event);
    }
}
