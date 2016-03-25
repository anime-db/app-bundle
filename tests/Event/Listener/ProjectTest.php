<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Event\Listener;

use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer;
use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters;
use AnimeDb\Bundle\AppBundle\Event\Listener\Project;
use AnimeDb\Bundle\AppBundle\Command\ProposeUpdateCommand;
use AnimeDb\Bundle\AppBundle\Service\CacheClearer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Test listener project
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class ProjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheClearer
     */
    protected $cache_clearer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Composer
     */
    protected $composer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    protected $em;

    /**
     * @var Project
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Parameters
     */
    protected $parameters;

    protected function setUp()
    {
        $this->parameters = $this
            ->getMockBuilder('\AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache_clearer = $this
            ->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\CacheClearer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->composer = $this
            ->getMockBuilder('\AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this
            ->getMockBuilder('\Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new Project($this->em, $this->cache_clearer, $this->composer, $this->parameters);
    }

    /**
     * Test on updated propose update task
     */
    public function testOnUpdatedProposeUpdateTask()
    {
        $that = $this;
        $next_run = new \DateTime();
        $next_run->modify('+'.ProposeUpdateCommand::INERVAL_UPDATE.' seconds  01:00:00');

        $task = $this->getMock('\AnimeDb\Bundle\AppBundle\Entity\Task');
        $task
            ->expects($this->once())
            ->method('setNextRun')
            ->will($this->returnCallback(function ($date) use ($that, $next_run, $task) {
                $that->assertEquals($next_run, $date);
                return $task;
            }));
        $rep = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $rep
            ->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue($task))
            ->with(['command' => 'animedb:propose-update']);
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with('AnimeDbAppBundle:Task')
            ->will($this->returnValue($rep));
        $this->em
            ->expects($this->once())
            ->method('persist')
            ->with($task);
        $this->em
            ->expects($this->once())
            ->method('flush');

        // test
        $this->listener->onUpdatedProposeUpdateTask();
    }

    public function testOnInstalledOrUpdatedAddShmop()
    {
        if (extension_loaded('shmop')) {
            $this->composer
                ->expects($this->once())
                ->method('addPackage')
                ->with('anime-db/shmop', '1.0.*');
        } else {
            $this->composer
                ->expects($this->once())
                ->method('removePackage')
                ->with('anime-db/shmop');
        }

        $this->listener->onInstalledOrUpdatedAddShmop();
    }
}
