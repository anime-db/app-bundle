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

use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Installed as InstalledEvent;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Removed as RemovedEvent;
use AnimeDb\Bundle\AnimeDbBundle\Event\Package\Updated as UpdatedEvent;
use AnimeDb\Bundle\AppBundle\Entity\Plugin;
use Guzzle\Http\Client;

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
     * API server host
     *
     * @var string
     */
    const API_HOST = 'http://anime-db.org/';

    /**
     * API version
     *
     * @var string
     */
    const API_VERSION = 1;

    /**
     * Entity manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Filesystem
     *
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * Locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Construct
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Symfony\Component\Filesystem\Filesystem $em
     * @param string $locale
     */
    public function __construct(EntityManager $em, Filesystem $fs, $locale)
    {
        $this->em = $em;
        $this->fs = $fs;
        $this->locale = $locale;
    }

    /**
     * Update plugin data
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Project\Updated $event
     */
    public function onUpdated(UpdatedEvent $event)
    {
        if ($event->getPackage()->getType() == self::PLUGIN_TYPE) {
            $plugin = $this->em->getRepository('AnimeDbAppBundle:Plugin')
                ->find($event->getPackage()->getName());

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
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Project\Installed $event
     */
    public function onInstalled(InstalledEvent $event)
    {
        if ($event->getPackage()->getType() == self::PLUGIN_TYPE) {
            $plugin = new Plugin();
            $plugin->setName($event->getPackage()->getName());
            $this->fillPluginData($plugin);

            $this->em->persist($plugin);
            $this->em->flush();
        }
    }

    /**
     * Unregistr plugin
     *
     * @param \AnimeDb\Bundle\AnimeDbBundle\Event\Project\Removed $event
     */
    public function onRemoved(RemovedEvent $event)
    {
        if ($event->getPackage()->getType() == self::PLUGIN_TYPE) {
            $plugin = $this->em->getRepository('AnimeDbAppBundle:Plugin')
                ->find($event->getPackage()->getName());

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
        $path = 'api/v'.self::API_VERSION.'/'.$this->locale.'/plugin/'.$plugin->getName().'/';
        $client = new Client(self::API_HOST);
        /* @var $response \Guzzle\Http\Message\Response */
        $response = $client->get($path)->send();

        if ($response->isSuccessful()) {
            $data = json_decode($response->getBody(true), true);
            $plugin->setTitle($data['title'])->setDescription($data['description']);

            if ($data['logo']) {
                if (!file_exists($plugin->getUploadRootDir())) {
                    $this->fs->mkdir($plugin->getUploadRootDir());
                }
                $plugin->setLogo(pathinfo($data['logo'], PATHINFO_BASENAME));
                copy($data['logo'], $plugin->getAbsolutePath());
            }
        }

        return $plugin;
    }
}