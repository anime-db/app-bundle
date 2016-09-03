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
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\BaseEntity;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\ImageInterface;

/**
 * Installed plugin.
 *
 * @ORM\Entity
 * @ORM\Table(name="plugin")
 */
class Plugin extends BaseEntity implements ImageInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $name = '';

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $title = '';

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $description = '';

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     *
     * @var string
     */
    protected $logo = '';

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     *
     * @var \DateTime
     */
    protected $date_install;

    public function __construct()
    {
        $this->date_install = new \DateTime();
    }

    /**
     * @param string $name
     *
     * @return Plugin
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $title
     *
     * @return Plugin
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $description
     *
     * @return Plugin
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $logo
     *
     * @return Plugin
     */
    public function setLogo($logo)
    {
        $this->setFilename($logo);

        return $this;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->getFilename();
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->logo ?: parent::getFilename();
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->logo = $filename;
        parent::setFilename($filename);
    }

    /**
     * @param \DateTime $date_install
     *
     * @return Plugin
     */
    public function setDateInstall(\DateTime $date_install)
    {
        $this->date_install = $date_install;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateInstall()
    {
        return $this->date_install;
    }

    /**
     * @return string
     */
    public function getDownloadPath()
    {
        return parent::getDownloadPath().'/plugin/'.$this->getName();
    }

    /**
     * Get logo web path.
     *
     * @deprecated use getWebPath()
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getLogoWebPath()
    {
        return $this->getWebPath();
    }
}
