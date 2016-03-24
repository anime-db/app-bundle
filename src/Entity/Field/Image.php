<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Entity\Field;

use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\BaseEntity;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\ImageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Item image
 *
 * @package AnimeDb\Bundle\AppBundle\Entity\Field
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Image extends BaseEntity implements ImageInterface
{
    /**
     * Image from URL
     *
     * @Assert\Url()
     *
     * @var string
     */
    protected $remote = '';

    /**
     * Local image
     *
     * @Assert\Image(
     *     maxSize = "2048k",
     *     minWidth = 200,
     *     minHeight = 200,
     *     mimeTypes = {"image/bmp","image/gif","image/jpeg","image/png"},
     *     mimeTypesMessage = "Please upload a valid image file"
     * )
     *
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile|null
     */
    protected $local;

    /**
     * Set remote image
     *
     * @param string $remote
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Field\Image
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
        return $this;
    }

    /**
     * Get remote image
     *
     * @return string
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * Set local image
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $local
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Field\Image
     */
    public function setLocal(UploadedFile $local)
    {
        $this->local = $local;
        return $this;
    }

    /**
     * Get local image
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        // it is a filename prefix, not a suffix for the download path
        if (strpos($filename, 'tmp/') !== 0) {
            $filename = 'tmp/'.date('Ymd').'/'.$filename;
        }
        parent::setFilename($filename);
    }

    /**
     * Clear local and remote file
     */
    public function clear()
    {
        $this->remote = '';
        $this->local = null;
    }

    /**
     * Has remote or local image
     *
     * @Assert\True(message = "No selected image")
     * 
     * @return boolean
     */
    public function hasImage()
    {
        return $this->remote || !is_null($this->local);
    }
}
