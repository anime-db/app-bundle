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
use Symfony\Component\Filesystem\Filesystem;
use AnimeDb\Bundle\ApiClientBundle\Service\Client;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed as InstalledEvent;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed as RemovedEvent;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Updated as UpdatedEvent;
use AnimeDb\Bundle\AppBundle\Entity\Plugin;
use Symfony\Component\Yaml\Yaml;

/**
 * Package listener
 *
 * @package AnimeDb\Bundle\AppBundle\Event\Listener
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Package
{
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
     * Filesystem
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * API client
     *
     * @var \AnimeDb\Bundle\ApiClientBundle\Service\Client
     */
    protected $client;

    /**
     * Path to parameters
     *
     * @var string
     */
    protected $parameters;

    /**
     * Construct
     *
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     * @param \AnimeDb\Bundle\ApiClientBundle\Service\Client $client
     * @param string $parameters
     */
    public function __construct(Registry $doctrine, Filesystem $fs, Client $client, $parameters)
    {
        $this->fs = $fs;
        $this->client = $client;
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
            $plugin = $this->rep->find($event->getPackage()->getName());

            // create new plugin if not exists
            if (!$plugin) {
                $plugin = new Plugin();
                $plugin->setName($event->getPackage()->getName());
            }

            $this->em->persist($this->fillPluginData($plugin));
            $this->em->flush();
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
            $plugin = $this->rep->find($event->getPackage()->getName());

            // create new plugin if not exists #55
            if (!$plugin) {
                $plugin = new Plugin();
                $plugin->setName($event->getPackage()->getName());
            }

            $this->em->persist($this->fillPluginData($plugin));
            $this->em->flush();
        }
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
     * Fill plugin data from server API
     *
     * @param \AnimeDb\Bundle\AppBundle\Entity\Plugin $plugin
     *
     * @return \AnimeDb\Bundle\AppBundle\Entity\Plugin
     */
    protected function fillPluginData(Plugin $plugin)
    {
        list($vendor, $package) = explode('/', $plugin->getName());

        try {
            $data = $this->client->getPlugin($vendor, $package);
            $plugin->setTitle($data['title'])->setDescription($data['description']);
            if ($data['logo']) {
                $plugin->setLogo(pathinfo($data['logo'], PATHINFO_BASENAME));
                $this->fs->mirror($data['logo'], $plugin->getAbsolutePath());
            }
        } catch (\RuntimeException $e) {} // is not a critical error

        return $plugin;
    }

    /**
     * Configure shmop
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed $event
     */
    public function onInstalledConfigureShmop(InstalledEvent $event)
    {
        // use Shmop as driver for Cache Time Keeper
        if ($event->getPackage()->getName() == 'anime-db/shmop') {
            $parameters = Yaml::parse($this->parameters);
            $parameters['parameters']['cache_time_keeper.driver'] = 'cache_time_keeper.driver.multi';
            $parameters['parameters']['cache_time_keeper.driver.multi.fast'] = 'cache_time_keeper.driver.shmop';
            $this->fs->dumpFile($this->parameters, Yaml::dump($parameters), 0644);
        }
    }

    /**
     * Restore config on removed shmop
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed $event
     */
    public function onRemovedShmop(RemovedEvent $event)
    {
        if ($event->getPackage()->getName() == 'anime-db/shmop') {
            $parameters = Yaml::parse($this->parameters);
            $parameters['parameters']['cache_time_keeper.driver'] = 'cache_time_keeper.driver.file';
            $this->fs->dumpFile($this->parameters, Yaml::dump($parameters), 0644);
        }
    }
}
