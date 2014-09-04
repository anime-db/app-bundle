<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Notice for user
 *
 * @ORM\Entity
 * @ORM\Table(name="notice", indexes={
 *   @ORM\Index(name="notice_show_idx", columns={"date_closed", "date_created"})
 * })
 * @ORM\Entity(repositoryClass="AnimeDb\Bundle\AppBundle\Repository\Notice")
 * @IgnoreAnnotation("ORM")
 *
 * @package AnimeDb\Bundle\AppBundle\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Notice
{
    /**
     * Status notice created
     *
     * @var integer
     */
    const STATUS_CREATED = 0;

    /**
     * Status notice shown
     *
     * @var integer
     */
    const STATUS_SHOWN = 1;

    /**
     * Status notice closed
     *
     * @var integer
     */
    const STATUS_CLOSED = 2;

    /**
     * Default lifetime
     *
     * @var integer
     */
    const DEFAULT_LIFETIME = 300;

    /**
     * Default type
     *
     * @var string
     */
    const DEFAULT_TYPE = 'no_type';

    /**
     * Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    protected $id;

    /**
     * Message
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $message = '';

    /**
     * Date closed notice
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     *
     * @var \DateTime|null
     */
    protected $date_closed = null;

    /**
     * Date created notice
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     *
     * @var \DateTime
     */
    protected $date_created;

    /**
     * Date start show notice
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     *
     * @var \DateTime
     */
    protected $date_start;

    /**
     * Lifetime notice
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     * @Assert\Type(type="integer", message="The value {{ value }} is not a valid {{ type }}.")
     *
     * @var integer
     */
    protected $lifetime = self::DEFAULT_LIFETIME;

    /**
     * Status
     *
     * @ORM\Column(type="integer")
     * @Assert\Choice(callback = "getStatuses")
     *
     * @var integer
     */
    protected $status = self::STATUS_CREATED;

    /**
     * Type
     *
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $type = self::DEFAULT_TYPE;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->date_created = $this->date_start = new \DateTime();
    }

    /**
     * Get supported statuses
     *
     * @return integer[]
     */
    public static function getStatuses()
    {
        return [self::STATUS_CREATED, self::STATUS_SHOWN, self::STATUS_CLOSED];
    }

    /**
     * Notice shown
     */
    public function shown()
    {
        if (is_null($this->date_closed)) {
            $this->date_closed = new \DateTime();
            $this->date_closed->modify('+'.$this->lifetime.' seconds');
        }
        $this->status = self::STATUS_SHOWN;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set date closed
     *
     * @param \DateTime $date_closed
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public function setDateClosed(\DateTime $date_closed)
    {
        $this->date_closed = clone $date_closed;
        return $this;
    }

    /**
     * Get date closed
     *
     * @return \DateTime|null
     */
    public function getDateClosed()
    {
        return $this->date_closed ? clone $this->date_closed : null;
    }

    /**
     * Get date created
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return clone $this->date_created;
    }

    /**
     * Set date start show
     *
     * @param \DateTime $date_start
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public function setDateStart(\DateTime $date_start)
    {
        $this->date_start = clone $date_start;
        return $this;
    }

    /**
     * Get date start show
     *
     * @return \DateTime
     */
    public function getDateStart()
    {
        return clone $this->date_start;
    }

    /**
     * Set lifetime
     *
     * @param integer $lifetime
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * Get lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}