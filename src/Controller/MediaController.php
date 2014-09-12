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
use Symfony\Component\HttpFoundation\Response;
use AnimeDb\Bundle\AppBundle\Service\Downloader;

/**
 * Media
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class MediaController extends Controller
{
    /**
     * Show favicon
     *
     * @param string $host
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function faviconAction($host)
    {
        $response = new Response();
        $response->headers->set('Content-Type', Downloader::FAVICON_MIME);

        $filename = $this->get('anime_db.downloader')->favicon($host);

        return $response->setContent(file_get_contents($filename));
    }
}
