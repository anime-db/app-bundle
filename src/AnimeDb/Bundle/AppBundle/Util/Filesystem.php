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

/**
 * Filesystem
 *
 * @package AnimeDb\Bundle\AppBundle\Util
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Filesystem
{
    /**
     * Get user home dir
     *
     * @return string
     */
    public static function getUserHomeDir() {
        // have home env var
        if ($home = getenv('HOME')) {
            return in_array(substr($home, -1), ['/', '\\']) ? $home : $home.DIRECTORY_SEPARATOR;
        }

        // *nix os
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $username = get_current_user() ?: getenv('USERNAME');
            return '/home/'.($username ? $username.'/' : '');
        }

        // have drive and path env vars
        if (getenv('HOMEDRIVE') && getenv('HOMEPATH')) {
            $home = getenv('HOMEDRIVE').getenv('HOMEPATH');
            $home = iconv('cp1251', 'utf-8', $home);
            return in_array(substr($home, -1), ['/', '\\']) ? $home : $home.DIRECTORY_SEPARATOR;
        }

        // Windows
        $username = get_current_user() ?: getenv('USERNAME');
        $username = iconv('cp1251', 'utf-8', $username);
        if ($username && is_dir($win7path = 'C:\Users\\'.$username.'\\')) { // is Vista or older
            return $win7path;
        } elseif ($username) {
            return 'C:\Documents and Settings\\'.$username.'\\';
        } elseif (is_dir('C:\Users\\')) { // is Vista or older
            return 'C:\Users\\';
        } else {
            return 'C:\Documents and Settings\\';
        }
    }
}