<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Command controller
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class CommandController extends Controller
{
    /**
     * Execute command in background
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function execAction(Request $request)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ini_set('memory_limit', -1);

        $command = $request->get('command');

        // change path for php
        if (substr($command, 0, 4) == 'php ') {
            $phpFinder = new PhpExecutableFinder();
            if (!($phpPath = $phpFinder->find())) {
                throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
            }
            $command = escapeshellarg($phpPath).substr($command, 3);
        }

        // change path to console
        $root = $this->container->getParameter('kernel.root_dir');
        $command = str_replace(' app/console ', ' '.escapeshellarg($root.DIRECTORY_SEPARATOR.'console').' ', $command);

        // change /dev/null for Windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $command = str_replace('/dev/null', 'nul', $command);
        }

        chdir($root.'/../');

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            pclose(popen('start /b call '.$command, 'r'));
        } else {
            exec($command.' &');
        }

        return new Response();
    }
}