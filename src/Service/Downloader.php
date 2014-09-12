<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\HttpException;

/**
 * Downloader
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Downloader
{
    /**
     * Favicon MIME type
     *
     * @var string
     */
    const FAVICON_MIME = 'image/x-icon';

    /**
     * Filesystem
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * HTTP client
     *
     * @var \Guzzle\Http\Client
     */
    protected $client;

    /**
     * Download root dir
     *
     * @var string
     */
    protected $root = '';

    /**
     * Download favicon root dir
     *
     * @var string
     */
    protected $favicon_root = '';

    /**
     * Favicon proxy downloader
     *
     * @var string
     */
    protected $favicon_proxy = '';

    /**
     * Construct
     *
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     * @param \Guzzle\Http\Client $client
     * @param string $root
     * @param string $favicon_root
     * @param string $favicon_proxy
     */
    public function __construct(Filesystem $fs, Client $client, $root, $favicon_root, $favicon_proxy)
    {
        $this->fs = $fs;
        $this->client = $client;
        $this->root = $root;
        $this->favicon_root = $favicon_root;
        $this->favicon_proxy = $favicon_proxy;
    }

    /**
     * Download file
     *
     * @param string $url
     * @param string $target
     * @param boolean $override
     *
     * @return boolean
     */
    public function download($url, $target, $override = false)
    {
        if (!$override && file_exists($target)) {
            return true;
        }

        $this->fs->mkdir(dirname($target), 0755);

        $response = $this->client
            ->get($url)
            ->setResponseBody($target)
            ->send();
        if ($response->isSuccessful()) {
            return true;
        }
        return false;
    }

    /**
     * Download image
     *
     * @param string $url
     * @param string $target
     * @param boolean $override
     *
     * @return boolean
     */
    public function image($url, $target, $override = false)
    {
        if (!$this->download($url, $target, $override)) {
            return false;
        }

        // check file
        if (@getimagesize($target) === false) {
            unlink($target); // remove dangerous file
            return false;
        }

        return true;
    }

    /**
     * Remote file is exists
     *
     * @param string $url
     *
     * @return boolean
     */
    public function isExists($url)
    {
        $request = $this->client->get($url);
        $request->getCurlOptions()->set(CURLOPT_NOBODY, true);

        try {
            return $request->send()->isSuccessful();
        } catch (HttpException $e) {
            return false;
        }
    }

    /**
     * Download
     *
     * @param string $host
     * @param boolean $override
     * 
     * @return string|false
     */
    public function favicon($host, $override = false)
    {
        $target = $this->getFaviconFilename($host);

        if ($this->image(sprintf($this->favicon_proxy, $host), $target, $override)) {
            return $target;
        }

        return false;
    }

    /**
     * Get favicon filename
     *
     * @param string $host
     *
     * @return string
     */
    protected function getFaviconFilename($host)
    {
        return $this->favicon_root.$host.'.ico';
    }
}
