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

use AnimeDb\Bundle\AppBundle\Entity\Notice;
use AnimeDb\Bundle\AppBundle\Repository\Notice as NoticeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NoticeController extends BaseController
{
    /**
     * Show last notice
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function showAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $rep NoticeRepository */
        $rep = $em->getRepository('AnimeDbAppBundle:Notice');

        $notice = $rep->getFirstShow();
        // caching
        /* @var $response JsonResponse */
        $response = $this->getCacheTimeKeeper()->getResponse([], -1, new JsonResponse());
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
                    'link_all' => $request->query->getBoolean('all')
                ])
            ]);
        }

        return $response;
    }

    /**
     * @param Notice $notice
     *
     * @return JsonResponse
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
     * @return JsonResponse
     */
    public function seeLaterAction()
    {
        /* @var $rep NoticeRepository */
        $rep =  $this->getDoctrine()->getRepository('AnimeDbAppBundle:Notice');
        $rep->seeLater();

        return new JsonResponse([]);
    }
}
