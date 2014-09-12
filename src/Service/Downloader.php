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
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\ImageInterface;

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

        return $this->client
            ->get($url)
            ->setResponseBody($target)
            ->send()
            ->isSuccessful();
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
     * Download favicon
     *
     * @param string $host
     * @param boolean $override
     * 
     * @return string|false
     */
    public function favicon($host, $override = false)
    {
        $target = $this->favicon_root.$host.'.ico';

        if ($this->image(sprintf($this->favicon_proxy, $host), $target, $override)) {
            return $target;
        }

        return false;
    }

    /**
     * Download entity
     *
     * @param string $url
     * @param \AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface $entity
     * @param boolean $override
     *
     * @return boolean
     */
    public function entity($url, EntityInterface $entity, $override = false)
    {
        if (!($path = parse_url($url, PHP_URL_PATH))) {
            throw new \InvalidArgumentException('It is invalid URL: '.$url);
        }
        $entity->setFilename(pathinfo($path, PATHINFO_BASENAME));
        $target = $this->root.$entity->getDownloadPath().'/'.$entity->getFilename();

        if ($entity instanceof ImageInterface) {
            return $this->image($url, $target, $override);
        } else {
            return $this->download($url, $target, $override);
        }
    }
}
