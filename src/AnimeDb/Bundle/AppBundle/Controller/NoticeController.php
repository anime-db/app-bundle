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
use AnimeDb\Bundle\AppBundle\Service\Pagination;

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

        // shown notice
        if (!is_null($notice)) {
            $notice->shown();
            $em->persist($notice);
            $em->flush();

            return new JsonResponse([
                'notice' => $notice->getId(),
                'close' => $this->generateUrl('notice_close', ['id' => $notice->getId()]),
                'see_later' => $this->generateUrl('notice_see_later'),
                'content' => $this->renderView('AnimeDbAppBundle:Notice:show.html.twig', [
                    'notice' => $notice,
                    'link_all' => $request->get('all')
                ])
            ]);
        }

        return new JsonResponse([]);
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
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();

        $list = $em->createQuery('
            SELECT
                n
            FROM
                AnimeDbAppBundle:Notice n
            WHERE
                n.status != :closed AND
                n.date_start <= :time AND
                (n.date_closed IS NULL OR n.date_closed >= :time)
            ORDER BY
                n.date_created, n.id ASC
        ')
            ->setParameter('closed', Notice::STATUS_CLOSED)
            ->setParameter('time', date('Y-m-d H:i:s'))
            ->getResult();

        // increase the date start display notice
        /* @var $notice \AnimeDb\Bundle\AppBundle\Entity\Notice */
        foreach ($list as $notice) {
            $notice->setDateStart(
                $notice->getDateStart()->modify('+'.self::SEE_LATER_INTERVAL.' seconds')
            );
            $em->persist($notice);
        }
        $em->flush();

        return new JsonResponse(['notices' => count($list)]);
    }
}