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

abstract class BaseEntity implements EntityInterface
{
    /**
     * @var string
     */
    private $filename = '';

    /**
     * @var array
     */
    private $old_filenames = [];

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
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
     * @return array
     */
    public function getOldFilenames()
    {
        return $this->old_filenames;
    }

    /**
     * @return string
     */
    public function getDownloadPath()
    {
        return 'media';
    }

    /**
     * @return string
     */
    public function getWebPath()
    {
        return $this->getFilename() ? sprintf('/%s/%s', $this->getDownloadPath(), $this->getFilename()) : '';
    }
}
