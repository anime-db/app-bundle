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

use Patchwork\Utf8;

class Filesystem
{
    /**
     * @var int
     */
    const FILE = 1;

    /**
     * @var int
     */
    const DIRECTORY = 2;

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
     * Get user home directory
     *
     * @return string
     */
    public static function getUserHomeDir() {
        return self::getRealPath(self::doGetUserHomeDir());
    }

    /**
     * @return string
     */
    private static function doGetUserHomeDir()
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

    /**
     * List files and directories inside the specified path
     *
     * @param string $path
     * @param int $filter
     * @param int $order
     *
     * @return array
     */
    public static function scandir($path, $filter = 0, $order = SCANDIR_SORT_ASCENDING)
    {
        if (!$filter || (
            ($filter & self::FILE) != self::FILE &&
            ($filter & self::DIRECTORY) != self::DIRECTORY
        )) {
            $filter = self::FILE|self::DIRECTORY;
        }
        // add slash if need
        $path = self::getRealPath($path);
        // wrap path for current fs
        $wrap = Utf8::wrapPath($path);

        // scan directory
        $folders = [];
        foreach (new \DirectoryIterator($wrap) as $file) {
            /* @var $file \SplFileInfo */
            try {
                if (
                    $file->getFilename()[0] != '.' &&
                    substr($file->getFilename(), -1) != '~' &&
                    $file->getFilename() != 'pagefile.sys' && // failed read C:\pagefile.sys
                    $file->isReadable() &&
                    (
                        (($filter & self::FILE) == self::FILE && $file->isFile()) ||
                        (($filter & self::DIRECTORY) == self::DIRECTORY && $file->isDir())
                    )
                ) {
                    $folders[$file->getFilename()] = [
                        'name' => $file->getFilename(),
                        'path' => $path . $file->getFilename() . DIRECTORY_SEPARATOR
                    ];
                }
            } catch (\Exception $e) {
                // ignore all errors
            }
        }

        // order files
        if ($order == SCANDIR_SORT_ASCENDING) {
            ksort($folders);
        } elseif ($order == SCANDIR_SORT_DESCENDING) {
            ksort($folders);
            $folders = array_reverse($folders);
        }

        // add link on parent folder
        if (substr_count($path, DIRECTORY_SEPARATOR) > 1) {
            $pos = strrpos(substr($path, 0, -1), DIRECTORY_SEPARATOR) + 1;
            array_unshift($folders, [
                'name' => '..',
                'path' => substr($path, 0, $pos)
            ]);
        }

        return array_values($folders);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function getRealPath($path)
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }
}
