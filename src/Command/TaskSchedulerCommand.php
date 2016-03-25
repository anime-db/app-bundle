<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AnimeDb\Bundle\AppBundle\Entity\Task;
use AnimeDb\Bundle\AppBundle\Repository\Task as TaskRepository;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Task Scheduler
 *
 * @package AnimeDb\Bundle\AppBundle\Command
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class TaskSchedulerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('animedb:task-scheduler')
            ->setDescription('Task Scheduler');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // exit if disabled
        if (!$this->getContainer()->getParameter('task_scheduler.enabled')) {
            return true;
        }

        // path to php executable
        $finder = new PhpExecutableFinder();
        $console = $finder->find().' '.$this->getContainer()->getParameter('kernel.root_dir').'/console';

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();
        /* @var $repository TaskRepository */
        $repository = $em->getRepository('AnimeDbAppBundle:Task');

        $output->writeln('Task Scheduler');

        while (true) {
            $task = $repository->getNextTask();

            // task is exists
            if ($task instanceof Task) {
                $output->writeln(sprintf('Run <info>%s</info>', $task->getCommand()));

                if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                    pclose(popen('start /b '.$console.' '.$task->getCommand().' >nul 2>&1', 'r'));
                } else {
                    exec($console.' '.$task->getCommand().' >/dev/null 2>&1 &');
                }

                // update information on starting
                $task->executed();
                $em->persist($task);
                $em->flush($task);
            }

            // standby for the next task
            $time = $repository->getWaitingTime();
            if ($time) {
                $output->writeln(sprintf('Wait <comment>%s</comment> s.', $time));
                sleep($time);
            }

            unset($task);
            gc_collect_cycles();
        }

        return true;
    }
}
