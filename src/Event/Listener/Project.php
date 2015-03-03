<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Event\Listener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use AnimeDb\Bundle\AppBundle\Command\ProposeUpdateCommand;
use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer;
use AnimeDb\Bundle\AppBundle\Service\CacheClearer;

/**
 * Project listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Project
{
    /**
     * Entity manager
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $em;

    /**
     * Cache clearer
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\CacheClearer
     */
    protected $cache_clearer;

    /**
     * Composer manipulator
     *
     * @var \AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer
     */
    protected $composer;

    /**
     * Construct
     *
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @param \AnimeDb\Bundle\AppBundle\Service\CacheClearer $cache_clearer
     * @param \AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer $composer
     */
    public function __construct(
        Registry $doctrine,
        CacheClearer $cache_clearer,
        Composer $composer
    ) {
        $this->em = $doctrine->getManager();
        $this->cache_clearer = $cache_clearer;
        $this->composer = $composer;
    }

    /**
     * Update next run date for the propose update task
     */
    public function onUpdatedProposeUpdateTask()
    {
        /* @var $task \AnimeDb\Bundle\AppBundle\Entity\Task */
        $task = $this->em
            ->getRepository('AnimeDbAppBundle:Task')
            ->findOneBy(['command' => 'animedb:propose-update']);

        $next_run = new \DateTime();
        $next_run->modify('+'.ProposeUpdateCommand::INERVAL_UPDATE.' seconds  01:00:00');

        $this->em->persist($task->setNextRun($next_run));
        $this->em->flush();
    }

    /**
     * On installed or updated try add a Shmop package
     */
    public function onInstalledOrUpdatedAddShmop()
    {
        // if the extension shmop is installed, can use the appropriate driver for store the key cache
        if (extension_loaded('shmop')) {
            $this->composer->addPackage('anime-db/shmop', '1.0.*');
        } else {
            $this->composer->removePackage('anime-db/shmop');
        }
    }
}
