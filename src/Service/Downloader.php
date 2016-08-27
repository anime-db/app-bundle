<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\HttpException;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\ImageInterface;
use AnimeDb\Bundle\AppBundle\Entity\Field\Image;

class Downloader
{
    /**
     * @var string
     */
    const FAVICON_MIME = 'image/x-icon';

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var string
     */
    protected $root = '';

    /**
     * @var string
     */
    protected $favicon_root = '';

    /**
     * @var string
     */
    protected $favicon_proxy = '';

    /**
     * @param Filesystem $fs
     * @param Client $client
     * @param ValidatorInterface $validator
     * @param string $root
     * @param string $favicon_root
     * @param string $favicon_proxy
     */
    public function __construct(
        Filesystem $fs,
        Client $client,
        ValidatorInterface $validator,
        $root,
        $favicon_root,
        $favicon_proxy
    ) {
        $this->fs = $fs;
        $this->client = $client;
        $this->validator = $validator;
        $this->root = $root;
        $this->favicon_root = $favicon_root;
        $this->favicon_proxy = $favicon_proxy;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param string $url
     * @param string $target
     * @param bool $override
     *
     * @return bool
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
     * @param string $url
     * @param string $target
     * @param bool $override
     *
     * @return bool
     */
    public function image($url, $target, $override = false)
    {
        if (!$this->download($url, $target, $override)) {
            return false;
        }

        // check file type
        $fi = new \finfo(FILEINFO_MIME_TYPE);
        if (strpos($fi->file($target), 'image/') !== 0) {
            unlink($target); // remove dangerous file
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     *
     * @return bool
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
     * @param string $host
     * @param bool $override
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
     * @param string $url
     * @param EntityInterface $entity
     * @param bool $override
     *
     * @return bool
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

    /**
     * @param Image $entity
     * @param string $url
     * @param bool $override
     */
    public function imageField(Image $entity, $url = '', $override = false)
    {
        if ($url) {
            $entity->setRemote($url);
        }

        // upload remote file
        if (!($entity->getLocal() instanceof UploadedFile) && $entity->getRemote()) {
            if (!($path = parse_url($entity->getRemote(), PHP_URL_PATH))) {
                throw new \InvalidArgumentException('It is invalid URL: '.$entity->getRemote());
            }
            $entity->setFilename(pathinfo($path, PATHINFO_BASENAME));
            $target = $this->getTargetDirForImageField($entity, $override);

            if (!$this->image($entity->getRemote(), $target, $override)) {
                throw new \RuntimeException('Failed download image');
            }

            // set remote as local for validate
            $entity->setLocal(new UploadedFile(
                $target,
                pathinfo($entity->getFilename(), PATHINFO_BASENAME),
                getimagesize($target)['mime'],
                filesize($target),
                UPLOAD_ERR_OK
            ));
            // validate entity
            $errors = $this->validator->validate($entity);
            if ($errors->has(0)) {
                unlink($target);
                throw new \InvalidArgumentException($errors->get(0)->getMessage());
            }
            $entity->clear();
        }

        // upload local file
        if ($entity->getLocal() instanceof UploadedFile) {
            $entity->setFilename($entity->getLocal()->getClientOriginalName());
            $info = pathinfo($this->getTargetDirForImageField($entity, $override));

            // upload from original name
            $entity->getLocal()->move($info['dirname'], $info['basename']);
            $entity->clear();
        }
    }

    /**
     * @param Image $entity
     * @param bool $override
     *
     * @return string
     */
    protected function getTargetDirForImageField(Image $entity, $override = false)
    {
        $target = $this->root.$entity->getDownloadPath().'/'.$entity->getFilename();
        if (!$override) {
            $target = $this->getUniqueFilename($target);
            $entity->setFilename(pathinfo($target, PATHINFO_BASENAME)); // update filename
        }

        return $target;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getUniqueFilename($filename)
    {
        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.'.$info['extension'] : '';
        for ($i = 1; file_exists($info['dirname'].'/'.$name.$ext); ++$i) {
            $name = $info['filename'].'['.$i.']';
        }

        return $info['dirname'].'/'.$name.$ext;
    }
}
