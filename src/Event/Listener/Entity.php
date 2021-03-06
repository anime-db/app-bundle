<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Event\Listener;

use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\Event\LifecycleEventArgs;
use AnimeDb\Bundle\AppBundle\Service\Downloader\Entity\EntityInterface;

class Entity
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $root = '';

    /**
     * @param Filesystem $fs
     * @param string $root
     */
    public function __construct(Filesystem $fs, $root)
    {
        $this->fs = $fs;
        $this->root = $root;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof EntityInterface) {
            if ($entity->getFilename()) {
                $this->fs->remove($this->root.$entity->getDownloadPath().'/'.$entity->getFilename());
            }
            $this->removeOldFiles($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof EntityInterface) {
            $this->removeOldFiles($entity);
        }
    }

    /**
     * @param EntityInterface $entity
     */
    protected function removeOldFiles(EntityInterface $entity)
    {
        $root = $this->root.$entity->getDownloadPath().'/';
        foreach ($entity->getOldFilenames() as $filename) {
            $this->fs->remove($root.$filename);
        }
    }
}
