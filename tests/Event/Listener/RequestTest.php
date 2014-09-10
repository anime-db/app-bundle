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
     * Filesystem
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fs;

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
     * Cache clearer
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache_clearer;

    /**
     * Request listener
     *
     * @var \AnimeDb\Bundle\AppBundle\Event\Listener\Request
     */
    protected $listener;

    /**
     * Path to parameters
     *
     * @var string
     */
    protected $parameters;

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
        $this->parameters = tempnam(sys_get_temp_dir(), 'test');
        $this->translatable = $this->getMockBuilder('\Gedmo\Translatable\TranslatableListener')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = $this->getMock('\Symfony\Component\Validator\Validator\ValidatorInterface');
        $this->cache_clearer = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\CacheClearer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fs = $this->getMock('\Symfony\Component\Filesystem\Filesystem');

        $this->listener = new Request(
            $this->translatable,
            $this->validator,
            $this->cache_clearer,
            $this->fs,
            $this->parameters,
            $this->locale
        );
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->parameters);
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
