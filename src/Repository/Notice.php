<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AnimeDb\Bundle\AppBundle\Entity\Notice as NoticeEntity;

/**
 * Notice repository
 *
 * @package AnimeDb\Bundle\AppBundle\Repository
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Notice extends EntityRepository
{
    /**
     * See notices later interval
     *
     * @var integer
     */
    const SEE_LATER_INTERVAL = 3600;

    /**
     * Get first show notice
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice|null
     */
    public function getFirstShow()
    {
        return $this->getEntityManager()->createQuery('
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
            ->setMaxResults(1)
            ->setParameter('closed', NoticeEntity::STATUS_CLOSED)
            ->setParameter('time', date('Y-m-d H:i:s'))
            ->getOneOrNullResult();
    }

    /**
     * Get notice list
     *
     * @param integer $limit
     * @param integer $offset
     *
     * @return array [\AnimeDb\Bundle\AppBundle\Entity\Notice]
     */
    public function getList($limit, $offset = 0)
    {
        return $this->getEntityManager()->createQuery('
            SELECT
                n
            FROM
                AnimeDbAppBundle:Notice n
            ORDER BY
                n.date_created DESC
        ')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();
    }

    /**
     * Get count notices
     *
     * @return integer
     */
    public function count()
    {
        return $this->getEntityManager()->createQuery('
            SELECT
                COUNT(n)
            FROM
                AnimeDbAppBundle:Notice n
        ')->getSingleScalarResult();
    }

    /**
     * See later
     */
    public function seeLater()
    {
        $time = time();
        $start = date('Y-m-d H:i:s', $time+self::SEE_LATER_INTERVAL);
        $time = date('Y-m-d H:i:s', $time);

        // not shown notice
        $this->getEntityManager()
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
            ->setParameter('closed', NoticeEntity::STATUS_CLOSED)
            ->execute();

        // rigidly set closing date
        $this->getEntityManager()
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
            ->setParameter('closed', NoticeEntity::STATUS_CLOSED)
            ->setParameter('time', $time)
            ->execute();
    }

    /**
     * Remove notices
     *
     * @param array $notices
     */
    public function remove(array $notices)
    {
        $ids = [];
        /* @var $notice \AnimeDb\Bundle\AppBundle\Entity\Notice */
        foreach ($notices as $notice) {
            $ids[] = $notice->getId();
        }
        $this->_em
            ->createQuery('
                DELETE FROM
                    AnimeDbAppBundle:Notice n
                WHERE
                    n.id IN (:ids)'
            )
            ->setParameter('ids', $ids)
            ->execute();
    }

    /**
     * Set status for notices
     *
     * @param array $notices
     * @param string $status
     */
    public function setStatus(array $notices, $status)
    {
        $ids = [];
        /* @var $notice \AnimeDb\Bundle\AppBundle\Entity\Notice */
        foreach ($notices as $notice) {
            $ids[] = $notice->getId();
        }
        $this->getEntityManager()
            ->createQuery('
                UPDATE
                    AnimeDbAppBundle:Notice n
                SET
                    n.status = :status
                WHERE
                    n.id IN (:ids)
            ')
            ->setParameter('ids', $ids)
            ->setParameter('status', $status)
            ->execute();
    }

    /**
     * Get filtered query
     *
     * @param integer $status
     * @param string $type
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getFilteredQuery($status, $type)
    {
        $query = $this->createQueryBuilder('n');
        if (in_array($status, NoticeEntity::getStatuses())) {
            $query
                ->where('n.status = :status')
                ->setParameter('status', $status);
        }
        if ($type) {
            $query
                ->andWhere('n.type = :type')
                ->setParameter('type', $type);
        }
        return $query;
    }
}
