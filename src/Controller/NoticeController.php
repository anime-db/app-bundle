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
use AnimeDb\Bundle\AppBundle\Entity\Notice;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Notice
 *
 * @package AnimeDb\Bundle\AppBundle\Controller
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class NoticeController extends Controller
{
    /**
     * Show last notice
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $repository \AnimeDb\Bundle\AppBundle\Repository\Notice */
        $repository = $em->getRepository('AnimeDbAppBundle:Notice');

        $notice = $repository->getFirstShow();
        // caching
        $response = $this->get('cache_time_keeper')->getResponse([], -1, new JsonResponse());
        $response->setEtag(md5($notice ? $notice->getId() : 0));
        // response was not modified for this request
        if ($response->isNotModified($request)) {
            return $response;
        }

        // shown notice
        if (!is_null($notice)) {
            $notice->shown();
            $em->persist($notice);
            $em->flush();

            $response->setData([
                'notice' => $notice->getId(),
                'close' => $this->generateUrl('notice_close', ['id' => $notice->getId()]),
                'see_later' => $this->generateUrl('notice_see_later'),
                'content' => $this->renderView('AnimeDbAppBundle:Notice:show.html.twig', [
                    'notice' => $notice,
                    'link_all' => $request->get('all')
                ])
            ]);
        }

        return $response;
    }

    /**
     * Close notice
     *
     * @param \AnimeDb\Bundle\AppBundle\Entity\Notice $notice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function closeAction(Notice $notice)
    {
        // mark as closed
        $notice->setStatus(Notice::STATUS_CLOSED);
        $em = $this->getDoctrine()->getManager();
        $em->persist($notice);
        $em->flush();

        return new JsonResponse([]);
    }

    /**
     * See notices later
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function seeLaterAction()
    {
        $this->getDoctrine()->getRepository('AnimeDbAppBundle:Notice')->seeLater();
        return new JsonResponse([]);
    }
}
