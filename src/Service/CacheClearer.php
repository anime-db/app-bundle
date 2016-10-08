<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheClearer
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @param Filesystem $fs
     * @param string $root
     */
    public function __construct(Filesystem $fs, $root)
    {
        $this->fs = $fs;
        $this->root = $root;
    }

    public function clear()
    {
        try {
            $this->fs->remove($this->root.'/cache/'); // so quickly
        } catch (IOException $e) {
            // is not a critical error
        }
    }
}
