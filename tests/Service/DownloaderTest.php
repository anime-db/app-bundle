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
     * Validator
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

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
        $this->validator = $this->getMock('\Symfony\Component\Validator\Validator\ValidatorInterface');
        $this->request = $this->getMock('\Guzzle\Http\Message\RequestInterface');
        $this->response = $this->getMockBuilder('\Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $this->downloader = new Downloader(
            $this->fs,
            $this->client,
            $this->validator,
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
     * Test get root
     */
    public function testGetRoot()
    {
        $this->assertEquals($this->dir, $this->downloader->getRoot());
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

    /**
     * Test entity fail
     *
     * @expectedException \InvalidArgumentException
     */
    public function testEntityFail()
    {
        $entity = $this->getMock('\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface');
        $this->downloader->entity('http://example.com', $entity);
    }

    /**
     * Get entity
     *
     * @return array
     */
    public function getEntity()
    {
        return [
            [true, '\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface'],
            [false, '\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface'],
            [true, '\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\ImageInterface'],
            [false, '\AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\ImageInterface']
        ];
    }

    /**
     * Test entity
     *
     * @dataProvider getEntity
     *
     * @param boolean $is_successful
     * @param string $entity
     */
    public function testEntity($is_successful, $entity)
    {
        $file = $this->dir.'bar/foo';
        $url = 'http://example.com/test/foo';
        mkdir($this->dir.'bar');
        file_put_contents($file, base64_decode(self::IMAGE));
        $this->download($file, $is_successful, $url);

        $entity = $this->getMock($entity);
        $entity
            ->expects($this->once())
            ->method('setFilename')
            ->with('foo');
        $entity
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn('foo');
        $entity
            ->expects($this->once())
            ->method('getDownloadPath')
            ->willReturn('bar');
        // test
        $actual = $this->downloader->entity($url, $entity, true);
        $this->assertEquals($is_successful, $actual);
    }

    /**
     * Get toggle
     *
     * @return array
     */
    public function getToggle()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * Get toggle multilevel
     *
     * @return array
     */
    public function getToggleMultilevel()
    {
        return [
            [false, false],
            [false, true],
            [true, false],
            [true, true],
        ];
    }

    /**
     * Test image field local
     *
     * @dataProvider getToggleMultilevel
     *
     * @param boolean $override
     * @param boolean $exists
     */
    public function testImageFieldLocal($override, $exists)
    {
        if ($exists) {
            mkdir($this->dir.'bar');
            touch($this->dir.'bar/foo');
        }

        // Travis CI throws an error if not set constructor arguments #82
        $file = tempnam($this->dir, 'UploadedFile');
        $file = $this
            ->getMockBuilder('\Symfony\Component\HttpFoundation\File\UploadedFile')
            ->setConstructorArgs([
                $file,
                pathinfo($file, PATHINFO_BASENAME),
                'text/plain',
                0,
                UPLOAD_ERR_OK
            ])
            ->getMock();
        $file
            ->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn('foo');
        $file
            ->expects($this->once())
            ->method('getClientOriginalName')
            ->willReturn('foo');
        $file
            ->expects($this->once())
            ->method('move')
            ->with($this->dir.'bar', $exists && !$override ? 'foo[1]' : 'foo');

        $entity = $this->getMock('\AnimeDb\Bundle\AppBundle\Entity\Field\Image');
        $entity
            ->expects($this->atLeastOnce())
            ->method('getLocal')
            ->willReturn($file);
        $entity
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn('foo');
        $entity
            ->expects($this->at(3))
            ->method('setFilename')
            ->with('foo');
        if (!$override) {
            $entity
                ->expects($this->at(6))
                ->method('setFilename')
                ->with($exists ? 'foo[1]' : 'foo');
        }
        $entity
            ->expects($this->once())
            ->method('getDownloadPath')
            ->willReturn('bar');
        $entity
            ->expects($this->once())
            ->method('clear');

        // test
        $this->downloader->imageField($entity, '', $override);
    }

    /**
     * Test image field remote fail
     *
     * @dataProvider getToggle
     * @expectedException \InvalidArgumentException
     *
     * @param boolean $toggle
     */
    public function testImageFieldRemoteFail($toggle)
    {
        $remote = $toggle ? '///' : '';
        $url = $toggle ? '' : '///';
        $this->downloader->imageField($this->getImageFieldRemote($remote, $url), $url);
    }

    /**
     * Test image field remote bad image
     *
     * @dataProvider getToggleMultilevel
     * @expectedException \RuntimeException
     *
     * @param boolean $toggle
     * @param boolean $is_successful
     */
    public function testImageFieldRemoteBadImage($toggle, $is_successful)
    {
        $file = 'foo.txt';
        $path = 'bar';
        $remote = $toggle ? 'http://example.com/test/'.$file : '';
        $url = $toggle ? '' : 'http://example.com/test/'.$file;

        mkdir($this->dir.$path, 0755, true);
        touch($this->dir.$path.'/'.$file);
        $entity = $this->getImageFieldRemote($remote, $url);
        $entity
            ->expects($this->once())
            ->method('setFilename')
            ->with($file);
        $entity
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn($file);
        $entity
            ->expects($this->once())
            ->method('getDownloadPath')
            ->willReturn($path);
        $this->download($this->dir.$path.'/'.$file, $is_successful, $url ?: $remote);

        // test
        $this->downloader->imageField($entity, $url, true);
    }

    /**
     * Test image field remote validator fail
     *
     * @dataProvider getToggle
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Error message
     *
     * @param boolean $toggle
     */
    public function testImageFieldRemoteValidatorFail($toggle)
    {
        $this->downloadImageFieldRemote($toggle, 'Error message');
    }

    /**
     * Test image field remote
     *
     * @dataProvider getToggle
     *
     * @param boolean $toggle
     */
    public function testImageFieldRemote($toggle)
    {
        $this->downloadImageFieldRemote($toggle);
    }

    /**
     * Download image field remote
     *
     * @param boolean $toggle
     * @param string $message
     */
    protected function downloadImageFieldRemote($toggle, $message = '')
    {
        $that = $this;
        $file = 'foo.txt';
        $path = 'bar';
        $target = $this->dir.$path.'/'.$file;
        $remote = $toggle ? 'http://example.com/test/'.$file : '';
        $url = $toggle ? '' : 'http://example.com/test/'.$file;
        mkdir($this->dir.$path, 0755, true);
        file_put_contents($target, base64_decode(self::IMAGE));

        $entity = $this->getImageFieldRemote($remote, $url);
        $entity
            ->expects($this->once())
            ->method('setFilename')
            ->with($file);
        $entity
            ->expects($this->atLeastOnce())
            ->method('getFilename')
            ->willReturn($file);
        $entity
            ->expects($this->once())
            ->method('getDownloadPath')
            ->willReturn($path);
        $entity
            ->expects($this->once())
            ->method('setLocal')
            ->willReturnCallback(function ($uploaded_file) use ($that, $target, $file) {
                // test uploaded file
                $that->assertInstanceOf('\Symfony\Component\HttpFoundation\File\UploadedFile', $uploaded_file);
                $that->assertEquals($target, $uploaded_file->getPathname());
                $that->assertEquals($file, $uploaded_file->getClientOriginalName());
                $that->assertEquals(getimagesize($target)['mime'], $uploaded_file->getClientMimeType());
                $that->assertEquals(filesize($target), $uploaded_file->getClientSize());
                $that->assertEquals(UPLOAD_ERR_OK, $uploaded_file->getError());
            });
        $entity
            ->expects($message ? $this->never() : $this->once())
            ->method('clear');
        // validation
        $list = $this->getMock('\Symfony\Component\Validator\ConstraintViolationListInterface');
        $list
            ->expects($this->once())
            ->method('has')
            ->willReturn(!!$message)
            ->with(0);
        if ($message) {
            $error = $this->getMock('\Symfony\Component\Validator\ConstraintViolationInterface');
            $error
                ->expects($this->once())
                ->method('getMessage')
                ->willReturn($message);
            $list
                ->expects($this->once())
                ->method('get')
                ->willReturn($error)
                ->with(0);
        }
        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($list)
            ->with($entity);
        $this->download($this->dir.$path.'/'.$file, true, $url ?: $remote);

        // test
        $this->downloader->imageField($entity, $url, true);
    }

    /**
     * Get image field remote
     *
     * @param string $remote
     * @param string $url
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getImageFieldRemote($remote, $url)
    {
        $entity = $this->getMock('\AnimeDb\Bundle\AppBundle\Entity\Field\Image');
        if ($url) {
            $entity
                ->expects($this->atLeastOnce())
                ->method('setRemote')
                ->with($url);
        }
        $entity
            ->expects($this->atLeastOnce())
            ->method('getRemote')
            ->willReturn($url ?: $remote);
        $entity
            ->expects($this->atLeastOnce())
            ->method('getLocal')
            ->willReturn(null);
        return $entity;
    }

    /**
     * Test get unique filename
     */
    public function testGetUniqueFilename()
    {
        $filename = $this->dir.'foo.txt';
        touch($new = $this->downloader->getUniqueFilename($filename));
        $this->assertEquals($this->dir.'foo.txt', $new);

        touch($new = $this->downloader->getUniqueFilename($filename));
        $this->assertEquals($this->dir.'foo[1].txt', $new);

        $this->assertEquals($this->dir.'foo[2].txt', $this->downloader->getUniqueFilename($filename));
    }
}