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

use AnimeDb\Bundle\AppBundle\Command\ProposeUpdateCommand;
use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer;
use AnimeDb\Bundle\AppBundle\Entity\Task;
use AnimeDb\Bundle\AppBundle\Service\CacheClearer;
use Doctrine\ORM\EntityManagerInterface;

class Project
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var CacheClearer
     */
    protected $cache_clearer;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param EntityManagerInterface $em
     * @param CacheClearer $cache_clearer
     * @param Composer $composer
     */
    public function __construct(
        EntityManagerInterface $em,
        CacheClearer $cache_clearer,
        Composer $composer
    ) {
        $this->em = $em;
        $this->cache_clearer = $cache_clearer;
        $this->composer = $composer;
    }

    /**
     * Update next run date for the propose update task
     */
    public function onUpdatedProposeUpdateTask()
    {
        /* @var $task Task */
        $task = $this->em
            ->getRepository('AnimeDbAppBundle:Task')
            ->findOneBy(['command' => 'animedb:propose-update']);

        $next_run = new \DateTime();
        $next_run->modify(sprintf('+%s seconds  01:00:00', ProposeUpdateCommand::INERVAL_UPDATE));

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
