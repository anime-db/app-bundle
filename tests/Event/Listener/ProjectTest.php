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

use AnimeDb\Bundle\AppBundle\Event\Listener\Project;
use AnimeDb\Bundle\AppBundle\Command\ProposeUpdateCommand;
use Symfony\Component\Yaml\Yaml;

/**
 * Test listener project
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class ProjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Filesystem
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fs;

    /**
     * Cache clearer
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache_clearer;

    /**
     * Composer
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * Entity manager
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * Project listener
     *
     * @var \AnimeDb\Bundle\AppBundle\Event\Listener\Project
     */
    protected $listener;

    /**
     * Path to parameters
     *
     * @var string
     */
    protected $parameters;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->parameters = tempnam(sys_get_temp_dir(), 'test');
        $this->fs = $this->getMock('\Symfony\Component\Filesystem\Filesystem');
        $this->cache_clearer = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\CacheClearer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->composer = $this->getMockBuilder('\AnimeDb\Bundle\AnimeDbBundle\Manipulator\Composer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($this->em);

        $this->listener = new Project($doctrine, $this->fs, $this->cache_clearer, $this->composer, $this->parameters);
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->parameters);
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
            ->willReturnCallback(function ($date) use ($that, $next_run, $task) {
                $that->assertEquals($next_run, $date);
                return $task;
            });
        $rep = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $rep
            ->expects($this->any())
            ->method('findOneBy')
            ->willReturn($task)
            ->with(['command' => 'animedb:propose-update']);
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with('AnimeDbAppBundle:Task')
            ->willReturn($rep);
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

    /**
     * Test on installed or updated try add a Shmop package
     */
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
                ->method('addPackage')
                ->with('anime-db/shmop');
        }
        $this->listener->onInstalledOrUpdatedAddShmop();
    }
}
