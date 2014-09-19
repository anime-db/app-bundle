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

use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\Event\LifecycleEventArgs;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface;

/**
 * Entity listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Entity
{
    /**
     * Filesystem
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * Download root dir
     *
     * @var string
     */
    protected $root = '';

    /**
     * Construct
     *
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     * @param string $root
     */
    public function __construct(Filesystem $fs, $root)
    {
        $this->fs = $fs;
        $this->root = $root;
    }

    /**
     * Post remove
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof EntityInterface) {
            $entity = $args->getEntity();
            if ($entity->getFilename()) {
                $this->fs->remove($this->root.$entity->getDownloadPath().'/'.$entity->getFilename());
            }
            $this->removeOldFiles($entity);
        }
    }

    /**
     * Post update
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof EntityInterface) {
            $this->removeOldFiles($args->getEntity());
        }
    }

    /**
     * Remove old files
     *
     * @param \AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface $entity
     */
    protected function removeOldFiles(EntityInterface $entity)
    {
        $root = $this->root.$entity->getDownloadPath().'/';
        foreach ($entity->getOldFilenames() as $filename) {
            $this->fs->remove($root.$filename);
        }
    }
}
