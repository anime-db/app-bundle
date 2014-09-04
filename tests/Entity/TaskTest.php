<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Entity;

use AnimeDb\Bundle\AppBundle\Entity\Task;

/**
 * Test task for Task Scheduler
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class TaskTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Task
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Task
     */
    protected $task;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->task = new Task();
    }

    /**
     * Test get id
     */
    public function testGetId()
    {
        $this->assertNull($this->task->getId());
    }

    /**
     * Test get statuses
     */
    public function testGetStatuses()
    {
        $this->assertEquals([Task::STATUS_DISABLED, Task::STATUS_ENABLED], Task::getStatuses());
    }

    /**
     * Get intervals
     *
     * @return array
     */
    public function getIntervals()
    {
        return [
            [0, ''],
            [-10, ''],
            [10, '+10 second']
        ];
    }

    /**
     * Test set interval
     *
     * @dataProvider getIntervals
     *
     * @param integer $interval
     * @param string $modify
     */
    public function testSetInterval($interval, $modify)
    {
        $this->assertEquals($this->task, $this->task->setInterval($interval));
        $this->assertEquals($modify, $this->task->getModify());
    }

    /**
     * Test is modify valid
     */
    public function testIsModifyValid()
    {
        $context = $this->getMock('\Symfony\Component\Validator\ExecutionContextInterface');
        $context
            ->expects($this->never())
            ->method('addViolationAt');
        // no modify
        $this->task->isModifyValid($context);
        // correct modify
        $this->task->setModify('+10 second');
        $this->task->isModifyValid($context);
    }

    /**
     * Test is modify valid fail
     */
    public function testIsModifyValidFail()
    {
        $context = $this->getMock('\Symfony\Component\Validator\ExecutionContextInterface');
        $context
            ->expects($this->once())
            ->method('addViolationAt')
            ->with('modify', 'Wrong date/time format');

        $this->task->setModify('--');
        $this->task->isModifyValid($context);
    }

    /**
     * Test executed not modify
     */
    public function testExecutedNotModify()
    {
        $this->task->setStatus(Task::STATUS_ENABLED);
        $this->executed();
    }

    /**
     * Test executed task is disabled
     */
    public function testExecutedDisabled()
    {
        $this->executed();
    }

    /**
     * Test executed bad modify
     */
    public function testExecutedBadModify()
    {
        $this->task->setModify('--');
        $this->task->setStatus(Task::STATUS_ENABLED);
        $next_run = $this->task->getNextRun();

        $this->executed();
        $this->assertEquals($next_run, $this->task->getNextRun());
        $this->assertEmpty($this->task->getModify());
    }

    /**
     * Test executed
     */
    public function testExecuted()
    {
        $modify = '+10 second';
        $this->task->setModify($modify);
        $this->task->setStatus(Task::STATUS_ENABLED);
        $next_run = $this->task->getNextRun();

        // actual next run date
        $this->task->setNextRun($this->task->getNextRun()->modify('-55 second'));

        // expected next run date
        $next_run->modify('+5 second');

        $this->executed(Task::STATUS_ENABLED);
        $this->assertEquals($next_run, $this->task->getNextRun());
        $this->assertEquals($modify, $this->task->getModify());
    }

    /**
     * Executed task
     *
     * @param string $status
     */
    protected function executed($status = Task::STATUS_DISABLED)
    {
        $this->task->executed();

        // test last run
        $this->assertEquals(new \Datetime(), $this->task->getLastRun());
        $this->assertEquals($status, $this->task->getStatus());
    }
}