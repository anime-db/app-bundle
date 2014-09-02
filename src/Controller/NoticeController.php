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
     * See notices later interval
     *
     * @var integer
     */
    const SEE_LATER_INTERVAL = 3600;

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
        $response = new JsonResponse();
        // project update date
        if ($last_update = $this->container->getParameter('last_update')) {
            $response->setLastModified(new \DateTime($last_update));
        }
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
        $time = time();
        $start = date('Y-m-d H:i:s', $time+self::SEE_LATER_INTERVAL);
        $time = date('Y-m-d H:i:s', $time);
        $em = $this->getDoctrine()->getManager();

        // not shown notice
        $em
            ->createQuery('
                UPDATE
                    AnimeDbAppBundle:Notice n
                SET
                    n.date_start = :start
                WHERE
                    n.status != :closed AND
                    n.date_closed IS NULL
            ')
            ->setParameter('start', $start)
            ->setParameter('closed', Notice::STATUS_CLOSED)
            ->execute();

        // rigidly set closing date
        $em
            ->createQuery('
                UPDATE
                    AnimeDbAppBundle:Notice n
                SET
                    n.date_start = :start,
                    n.date_closed = DATETIME(n.date_closed, :interval)
                WHERE
                    n.status != :closed AND
                    n.date_closed IS NOT NULL AND
                    n.date_closed > :time
            ')
            ->setParameter('start', $start)
            ->setParameter('interval', '+'.self::SEE_LATER_INTERVAL.' seconds')
            ->setParameter('closed', Notice::STATUS_CLOSED)
            ->setParameter('time', $time)
            ->execute();

        return new JsonResponse([]);
    }
}