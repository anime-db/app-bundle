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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Task
     */
    protected $task;

    protected function setUp()
    {
        $this->task = new Task();
    }

    public function testGetId()
    {
        $this->assertNull($this->task->getId());
    }

    public function testGetStatuses()
    {
        $this->assertEquals([Task::STATUS_DISABLED, Task::STATUS_ENABLED], Task::getStatuses());
    }

    /**
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

    public function testIsModifyValid()
    {
        /* @var $context \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface */
        $context = $this->getMock('\Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context
            ->expects($this->never())
            ->method('buildViolation');
        // no modify
        $this->task->isModifyValid($context);
        // correct modify
        $this->task->setModify('+10 second');
        $this->task->isModifyValid($context);
    }

    public function testIsModifyValidFail()
    {
        /* @var $violation \PHPUnit_Framework_MockObject_MockObject|ConstraintViolationBuilderInterface */
        $violation = $this->getMock('\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $violation
            ->expects($this->once())
            ->method('atPath')
            ->with('modify')
            ->will($this->returnSelf());
        $violation
            ->expects($this->once())
            ->method('addViolation');

        /* @var $context \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface */
        $context = $this->getMock('\Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context
            ->expects($this->once())
            ->method('buildViolation')
            ->with('Wrong date/time format')
            ->will($this->returnValue($violation));

        $this->task->setModify('--');
        $this->task->isModifyValid($context);
    }

    public function testExecutedNotModify()
    {
        $this->task->setStatus(Task::STATUS_ENABLED);
        $this->executed();
    }

    public function testExecutedDisabled()
    {
        $this->executed();
    }

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
     * @param int $status
     */
    protected function executed($status = Task::STATUS_DISABLED)
    {
        $this->task->executed();

        // test last run
        $this->assertEquals(new \Datetime(), $this->task->getLastRun());
        $this->assertEquals($status, $this->task->getStatus());
    }
}
