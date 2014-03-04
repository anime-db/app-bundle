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

/**
 * Assets
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class AssetsController extends Controller
{

    /**
     * Show assets javascripts
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function jsAction()
    {
        return $this->render('AnimeDbAppBundle:Assets:js.html.twig', [
            'paths' => $this->get('anime_db.assets')->getJavaScriptsPaths()
        ]);
    }

    /**
     * Show assets stylesheets
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cssAction()
    {
        return $this->render('AnimeDbAppBundle:Assets:css.html.twig', [
            'paths' => $this->get('anime_db.assets')->getStylesheetPaths()
        ]);
    }
}