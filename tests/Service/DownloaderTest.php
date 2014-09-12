<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Service;

use AnimeDb\Bundle\AppBundle\Service\Downloader;
use Symfony\Component\Filesystem\Filesystem;
use Guzzle\Http\Exception\RequestException;

/**
 * Test downloader
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class DownloaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Base64 image
     *
     * @var string
     */
    const IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';

    /**
     * Download dir
     *
     * @var string
     */
    protected $dir;

    /**
     * Download favicon dir
     *
     * @var string
     */
    protected $favicon;

    /**
     * Favicon proxy
     *
     * @var string
     */
    protected $proxy = 'http://www.google.com/s2/favicons?domain=%s';

    /**
     * Example URL
     *
     * @var string
     */
    protected $url = 'http://example.com/';

    /**
     * Filesystem
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fs;

    /**
     * Client
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * Request
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * Response
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * Downloader
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\Downloader
     */
    protected $downloader;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/test/';
        $this->favicon = $this->dir.'favicon/';
        mkdir($this->favicon, 0755, true);
        $this->fs = $this->getMock('\Symfony\Component\Filesystem\Filesystem');
        $this->client = $this->getMock('\Guzzle\Http\Client');
        $this->request = $this->getMock('\Guzzle\Http\Message\RequestInterface');
        $this->response = $this->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $this->downloader = new Downloader(
            $this->fs,
            $this->client,
            $this->dir,
            $this->favicon,
            $this->proxy
        );
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        (new Filesystem())->remove($this->dir);
    }

    /**
     * Test download file exists
     */
    public function testDownloadFileExists()
    {
        $file = tempnam($this->dir, 'test');
        $this->client
            ->expects($this->never())
            ->method('get');
        $this->assertTrue($this->downloader->download($this->url, $file));
    }

    /**
     * Get successfuls
     *
     * @return array
     */
    public function getSuccessfuls()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Test download
     *
     * @dataProvider getSuccessfuls
     *
     * @param boolean $is_successful
     */
    public function testDownload($is_successful)
    {
        $file = $this->dir.'test';
        $this->download($file, $is_successful);
        $actual = $this->downloader->download($this->url, $file, true);
        $this->assertEquals($is_successful, $actual);
    }

    /**
     * Test image bad image
     */
    public function testImageBadImage()
    {
        $file = tempnam($this->dir, 'test');
        $this->client
            ->expects($this->never())
            ->method('get');
        $this->assertFalse($this->downloader->image($this->url, $file));
    }

    /**
     * Test image fail download
     */
    public function testImageFailDownload()
    {
        $file = $this->dir.'test';
        $this->download($file, false);
        // test
        $this->assertFalse($this->downloader->image($this->url, $file, true));
    }

    /**
     * Test image
     */
    public function testImage()
    {
        $file = $this->dir.'test';
        file_put_contents($file, base64_decode(self::IMAGE));
        $this->download($file, true);
        // test
        $this->assertTrue($this->downloader->image($this->url, $file, true));
    }

    /**
     * Test is exists
     *
     * @dataProvider getSuccessfuls
     *
     * @param boolean $is_successful
     */
    public function testIsExists($is_successful)
    {
        $this->dialog($is_successful);
        $this->responseNoBody();
        // test
        $this->assertEquals($is_successful, $this->downloader->isExists($this->url));
    }

    /**
     * Test is exists exception
     */
    public function testIsExistsException()
    {
        $this->responseNoBody();
        $this->request
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new RequestException());
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with($this->url)
            ->willReturn($this->request);
        // test
        $this->assertFalse($this->downloader->isExists($this->url));
    }

    /**
     * Test favicon bad image
     */
    public function testFaviconBadImage()
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        file_put_contents($this->favicon.$host.'.ico', '');
        $this->client
            ->expects($this->never())
            ->method('get');
        $this->assertFalse($this->downloader->favicon($host));
    }

    /**
     * Test favicon fail download
     */
    public function testFaviconFailDownload()
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        $this->download($this->favicon.$host.'.ico', false, sprintf($this->proxy, $host));
        // test
        $this->assertFalse($this->downloader->favicon($host, true));
    }

    /**
     * Test favicon
     */
    public function testFavicon()
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        $file = $this->favicon.$host.'.ico';
        file_put_contents($file, base64_decode(self::IMAGE));
        $this->download($file, true, sprintf($this->proxy, $host));
        // test
        $this->assertEquals($file, $this->downloader->favicon($host, true));
    }

    /**
     * Download
     *
     * @param string $target
     * @param boolean $is_successful
     * @param string $url
     */
    protected function download($target, $is_successful = true, $url = '')
    {
        $this->fs
            ->expects($this->once())
            ->method('mkdir')
            ->with(dirname($target), 0755);
        $this->request
            ->expects($this->once())
            ->method('setResponseBody')
            ->willReturnSelf()
            ->with($target);
        $this->dialog($is_successful, $url);
    }

    /**
     * Create dialog
     *
     * @param boolean $is_successful
     * @param string $url
     */
    protected function dialog($is_successful = true, $url = '')
    {
        $this->response
            ->expects($this->once())
            ->method('isSuccessful')
            ->willReturn($is_successful);
        $this->request
            ->expects($this->once())
            ->method('send')
            ->willReturn($this->response);
        $this->client
            ->expects($this->once())
            ->method('get')
            ->with($url ?: $this->url)
            ->willReturn($this->request);
    }

    /**
     * Response no body
     */
    protected function responseNoBody()
    {
        $options = $this->getMock('\Guzzle\Common\Collection');
        $options
            ->expects($this->once())
            ->method('set')
            ->with(CURLOPT_NOBODY, true);
        $this->request
            ->expects($this->once())
            ->method('getCurlOptions')
            ->willReturn($options);
    }
}
