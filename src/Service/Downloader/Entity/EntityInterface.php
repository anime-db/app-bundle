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
 * Entity interface
 *
 * @package AnimeDb\Bundle\AppBundle\Service\Downloader\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
interface EntityInterface
{
    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename();

    /**
     * Set filename
     *
     * @param string $filename
     */
    public function setFilename($filename);

    /**
     * Get old filenames
     *
     * @return array
     */
    public function getOldFilenames();

    /**
     * Get download path
     *
     * @return string
     */
    public function getDownloadPath();

    /**
     * Get web path
     *
     * @return string
     */
    public function getWebPath();
}
