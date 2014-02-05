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

/**
 * Assets
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class AssetsController extends Controller
{

    /**
     * Show assets stylesheets and javascripts
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request)
    {
        $response = new Response();
        // caching
        if ($last_update = $this->container->getParameter('last_update')) {
            $response->setPublic();
            $response->setLastModified(new \DateTime($last_update));

            // response was not modified for this request
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

        return $this->render('AnimeDbAppBundle:Assets:show.html.twig', [
            'css' => $this->get('anime_db.assets')->getStylesheetPaths(),
            'js' => $this->get('anime_db.assets')->getJavaScriptsPaths()
        ], $response);
    }
}