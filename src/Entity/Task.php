<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Task for Task Scheduler.
 *
 * @ORM\Entity
 * @ORM\Table(name="task", indexes={
 *   @ORM\Index(name="idx_task_next_start", columns={"next_run", "status"})
 * })
 * @Assert\Callback(methods={"isModifyValid"})
 * @ORM\Entity(repositoryClass="AnimeDb\Bundle\AppBundle\Repository\Task")
 */
class Task
{
    /**
     * @var int
     */
    const STATUS_ENABLED = 1;

    /**
     * @var int
     */
    const STATUS_DISABLED = 0;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=128)
     *
     * @var string
     */
    protected $command = '';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     *
     * @var \DateTime|null
     */
    protected $last_run;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     *
     * @var \DateTime
     */
    protected $next_run;

    /**
     * A date/time string.
     *
     * Valid formats are explained in Date and Time Formats.
     *
     * @link http://www.php.net/manual/en/datetime.formats.php
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @var string
     */
    protected $modify = '';

    /**
     * @ORM\Column(type="integer")
     * @Assert\Choice(callback = "getStatuses")
     *
     * @var int
     */
    protected $status = self::STATUS_DISABLED;

    public function __construct()
    {
        $this->next_run = new \DateTime();
    }

    /**
     * @param string $command
     *
     * @return Task
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $last_run
     *
     * @return Task
     */
    public function setLastRun(\DateTime $last_run)
    {
        $this->last_run = clone $last_run;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastRun()
    {
        return $this->last_run ? clone $this->last_run : null;
    }

    /**
     * @param \DateTime $next_run
     *
     * @return Task
     */
    public function setNextRun(\DateTime $next_run)
    {
        $this->next_run = clone $next_run;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getNextRun()
    {
        return clone $this->next_run;
    }

    /**
     * @param int $interval
     *
     * @return Task
     */
    public function setInterval($interval)
    {
        if ($interval > 0) {
            $this->setModify(sprintf('+%s second', (int) $interval));
        }

        return $this;
    }

    /**
     * @param string $modify
     *
     * @return Task
     */
    public function setModify($modify)
    {
        $this->modify = $modify;

        return $this;
    }

    /**
     * @return string
     */
    public function getModify()
    {
        return $this->modify;
    }

    /**
     * @param int $status
     *
     * @return Task
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int[]
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_DISABLED,
            self::STATUS_ENABLED,
        ];
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function isModifyValid(ExecutionContextInterface $context)
    {
        if ($this->getModify() && strtotime($this->getModify()) === false) {
            $context
                ->buildViolation('Wrong date/time format')
                ->atPath('modify')
                ->addViolation();
        }
    }

    /**
     * Update task after execution.
     */
    public function executed()
    {
        $this->setLastRun(new \DateTime());
        if (!$this->getModify()) {
            $this->setStatus(self::STATUS_DISABLED);
        }
        if ($this->getStatus() == self::STATUS_ENABLED) {
            // find near time task launch
            $next_run = $this->getNextRun();
            do {
                // failed to compute time of next run
                if ($next_run->modify($this->getModify()) === false) {
                    $this->setModify('');
                    $this->setStatus(self::STATUS_DISABLED);
                    break;
                }
            } while ($next_run->getTimestamp() <= time());
            $this->setNextRun($next_run);
        }
    }
}
