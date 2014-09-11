<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Util;

use Symfony\Component\Filesystem\Filesystem as Component;

/**
 * Filesystem
 *
 * @package AnimeDb\Bundle\AppBundle\Util
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Filesystem
{
    /**
     * Filesystem
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * Construct
     *
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     */
    public function __construct(Component $fs)
    {
        $this->fs = $fs;
    }

    /**
     * Download image
     *
     * @param string $url
     * @param string $target
     * @param boolean $override
     */
    public function downloadImage($url, $target, $override = false)
    {
        $this->fs->copy($url, $target, $override);
        if (@getimagesize($target) === false) {
            unlink($target);
        }
    }

    /**
     * Gets the name of the owner of the current PHP script
     *
     * @return string
     */
    public static function getUserName()
    {
        return get_current_user() ?: getenv('USERNAME');
    }

    /**
     * Get user home dir
     *
     * @return string
     */
    public static function getUserHomeDir() {
        $home = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::doUserHomeDir());

        if (substr($home, -1) != DIRECTORY_SEPARATOR) {
            $home .= DIRECTORY_SEPARATOR;
        }
        return $home;
    }

    /**
     * Do user home dir
     *
     * @return string
     */
    private function doUserHomeDir()
    {
        // have home env var
        if ($home = getenv('HOME')) {
            return $home;
        }

        // *nix os
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            return '/home/'.self::getUserName();
        }

        // have drive and path env vars
        if (getenv('HOMEDRIVE') && getenv('HOMEPATH')) {
            return iconv('cp1251', 'utf-8', getenv('HOMEDRIVE').getenv('HOMEPATH'));
        }

        // Windows
        if ($username = self::getUserName()) {
            $username = iconv('cp1251', 'utf-8', $username);
            // is Vista or older
            if (is_dir($win7path = 'C:\Users\\'.$username.'\\')) {
                return $win7path;
            }
            return 'C:\Documents and Settings\\'.$username.'\\';
        }

        // is Vista or older
        if (is_dir('C:\Users\\')) {
            return 'C:\Users\\';
        }

        return 'C:\Documents and Settings\\';
    }
}
