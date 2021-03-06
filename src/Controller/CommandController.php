<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommandController extends Controller
{
    /**
     * Execute command in background.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function execAction(Request $request)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $this->get('anime_db.command')->execute($request->get('command'), 0);

        return new Response();
    }
}
