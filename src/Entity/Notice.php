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

/**
 * Notice for user.
 *
 * @ORM\Entity
 * @ORM\Table(name="notice", indexes={
 *   @ORM\Index(name="notice_show_idx", columns={"date_closed", "date_created"})
 * })
 * @ORM\Entity(repositoryClass="AnimeDb\Bundle\AppBundle\Repository\Notice")
 */
class Notice
{
    /**
     * @var int
     */
    const STATUS_CREATED = 0;

    /**
     * @var int
     */
    const STATUS_SHOWN = 1;

    /**
     * @var int
     */
    const STATUS_CLOSED = 2;

    /**
     * @var int
     */
    const DEFAULT_LIFETIME = 300;

    /**
     * @var string
     */
    const DEFAULT_TYPE = 'no_type';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $message = '';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     *
     * @var \DateTime|null
     */
    protected $date_closed = null;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     *
     * @var \DateTime
     */
    protected $date_created;

    /**
     * Date start show notice.
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     *
     * @var \DateTime
     */
    protected $date_start;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     * @Assert\Type(type="integer", message="The value {{ value }} is not a valid {{ type }}.")
     *
     * @var int
     */
    protected $lifetime = self::DEFAULT_LIFETIME;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Choice(callback = "getStatuses")
     *
     * @var int
     */
    protected $status = self::STATUS_CREATED;

    /**
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $type = self::DEFAULT_TYPE;

    public function __construct()
    {
        $this->date_created = $this->date_start = new \DateTime();
    }

    /**
     * Notice shown.
     */
    public function shown()
    {
        if (is_null($this->date_closed)) {
            $this->date_closed = new \DateTime();
            $this->date_closed->modify(sprintf('+%s seconds', $this->lifetime));
        }
        $this->status = self::STATUS_SHOWN;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $message
     *
     * @return Notice
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param \DateTime $date_closed
     *
     * @return Notice
     */
    public function setDateClosed(\DateTime $date_closed)
    {
        $this->date_closed = clone $date_closed;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateClosed()
    {
        return $this->date_closed ? clone $this->date_closed : null;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return clone $this->date_created;
    }

    /**
     * @param \DateTime $date_start
     *
     * @return Notice
     */
    public function setDateStart(\DateTime $date_start)
    {
        $this->date_start = clone $date_start;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateStart()
    {
        return clone $this->date_start;
    }

    /**
     * @param int $lifetime
     *
     * @return Notice
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * @return int
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @param int $status
     *
     * @return Notice
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
            self::STATUS_CREATED,
            self::STATUS_SHOWN,
            self::STATUS_CLOSED,
        ];
    }

    /**
     * @param string $type
     *
     * @return Notice
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
