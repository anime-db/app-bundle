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

use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Php finder
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class PhpFinder
{
    /**
     * Path to php executable
     *
     * @var string
     */
    private $php_path;

    /**
     * Finder
     *
     * @var \Symfony\Component\Process\PhpExecutableFinder
     */
    private $finder;

    /**
     * Construct
     *
     * @param \Symfony\Component\Process\PhpExecutableFinder $finder
     */
    public function __construct(PhpExecutableFinder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Get path to php executable
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function getPath()
    {
        if (!$this->php_path) {
            if (!($this->php_path = $this->finder->find())) {
                throw new \RuntimeException(
                    'The php executable could not be found, add it to your PATH environment variable and try again'
                );
            }
            $this->php_path = escapeshellarg($this->php_path);
        }
        return $this->php_path;
    }
}
