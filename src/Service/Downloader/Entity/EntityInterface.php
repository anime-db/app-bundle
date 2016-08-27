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

interface EntityInterface
{
    /**
     * @return string
     */
    public function getFilename();

    /**
     * @param string $filename
     */
    public function setFilename($filename);

    /**
     * @return array
     */
    public function getOldFilenames();

    /**
     * @return string
     */
    public function getDownloadPath();

    /**
     * @return string
     */
    public function getWebPath();
}
