<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Service\Downloader\Entity;

/**
 * Base entity
 *
 * @package AnimeDb\Bundle\AppBundle\Service\Downloader\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
abstract class BaseEntity implements EntityInterface
{
    /**
     * Filename
     *
     * @var string
     */
    private $filename = '';

    /**
     * Old filenames
     *
     * @var array
     */
    private $old_filenames = [];

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filename
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        // copy current file to the old files for remove it later
        if ($this->filename) {
            $this->old_filenames[] = $this->filename;
        }
        $this->filename = $filename;
    }

    /**
     * Get old filenames
     *
     * @return array
     */
    public function getOldFilenames()
    {
        return $this->old_filenames;
    }

    /**
     * Get download path
     *
     * @return string
     */
    public function getDownloadPath()
    {
        return 'media';
    }

    /**
     * Get web path
     *
     * @return string
     */
    public function getWebPath()
    {
        return $this->getFilename() ? '/'.$this->getDownloadPath().'/'.$this->getFilename() : '';
    }
}
