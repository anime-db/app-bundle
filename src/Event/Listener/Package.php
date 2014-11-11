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

use Doctrine\Bundle\DoctrineBundle\Registry;
use AnimeDb\Bundle\ApiClientBundle\Service\Client;
use AnimeDb\Bundle\AppBundle\Service\Downloader;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed as InstalledEvent;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed as RemovedEvent;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Updated as UpdatedEvent;
use AnimeDb\Bundle\AppBundle\Entity\Plugin;
use Composer\Package\Package as ComposerPackage;
use AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters;

/**
 * Package listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Package
{
    /**
     * Type of plugin package
     *
     * @var string
     */
    const PLUGIN_TYPE = 'anime-db-plugin';

    /**
     * Package shmop
     *
     * @var string
     */
    const PACKAGE_SHMOP = 'anime-db/shmop';

    /**
     * Entity manager
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $em;

    /**
     * Entity repository
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $rep;

    /**
     * API client
     *
     * @var \AnimeDb\Bundle\ApiClientBundle\Service\Client
     */
    protected $client;

    /**
     * Parameters manipulator
     *
     * @var \AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters
     */
    protected $parameters;

    /**
     * Downloader
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\Downloader
     */
    protected $downloader;

    /**
     * Construct
     *
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @param \AnimeDb\Bundle\ApiClientBundle\Service\Client $client
     * @param \AnimeDb\Bundle\AppBundle\Service\Downloader $downloader
     * @param \AnimeDb\Bundle\AnimeDbBundle\Manipulator\Parameters $parameters
     */
    public function __construct(
        Registry $doctrine,
        Client $client,
        Downloader $downloader,
        Parameters $parameters
    ) {
        $this->client = $client;
        $this->downloader = $downloader;
        $this->em = $doctrine->getManager();
        $this->parameters = $parameters;
        $this->rep = $this->em->getRepository('AnimeDbAppBundle:Plugin');
    }

    /**
     * Update plugin data
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Updated $event
     */
    public function onUpdated(UpdatedEvent $event)
    {
        if ($event->getPackage()->getType() == self::PLUGIN_TYPE) {
            $this->addPackage($event->getPackage());
        }
    }

    /**
     * Registr plugin
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed $event
     */
    public function onInstalled(InstalledEvent $event)
    {
        if ($event->getPackage()->getType() == self::PLUGIN_TYPE) {
            $this->addPackage($event->getPackage());
        }
    }

    /**
     * Add plugin from package
     *
     * @param \Composer\Package\Package $package
     */
    protected function addPackage(ComposerPackage $package)
    {
        $plugin = $this->rep->find($package->getName());

        // create new plugin if not exists
        if (!$plugin) {
            $plugin = new Plugin();
            $plugin->setName($package->getName());
        }

        list($vendor, $package) = explode('/', $plugin->getName());

        try {
            $data = $this->client->getPlugin($vendor, $package);
            $plugin->setTitle($data['title'])->setDescription($data['description']);
            if ($data['logo']) {
                $this->downloader->entity($data['logo'], $plugin, true);
            }
        } catch (\Exception $e) {} // is not a critical error

        $this->em->persist($plugin);
        $this->em->flush();
    }

    /**
     * Unregistr plugin
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed $event
     */
    public function onRemoved(RemovedEvent $event)
    {
        if ($event->getPackage()->getType() == self::PLUGIN_TYPE) {
            $plugin = $this->rep->find($event->getPackage()->getName());

            if ($plugin) {
                $this->em->remove($plugin);
                $this->em->flush();
            }
        }
    }

    /**
     * Configure shmop
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed $event
     */
    public function onInstalledConfigureShmop(InstalledEvent $event)
    {
        // use Shmop as driver for Cache Time Keeper
        if ($event->getPackage()->getName() == self::PACKAGE_SHMOP) {
            $this->parameters->set('cache_time_keeper.driver', 'cache_time_keeper.driver.multi');
            $this->parameters->set('cache_time_keeper.driver.multi.fast', 'cache_time_keeper.driver.shmop');
        }
    }

    /**
     * Restore config on removed shmop
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed $event
     */
    public function onRemovedShmop(RemovedEvent $event)
    {
        if ($event->getPackage()->getName() == self::PACKAGE_SHMOP) {
            $this->parameters->set('cache_time_keeper.driver', 'cache_time_keeper.driver.file');
        }
    }
}
