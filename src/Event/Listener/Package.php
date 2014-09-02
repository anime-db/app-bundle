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
     * API default locale
     *
     * @var string
     */
    const API_DEFAULT_LOCALE = 'en';

    /**
     * Entity manager
     *
     * @var \Doctrine\ORM\EntityManager
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
     * Locale
     *
     * @var string
     */
    protected $locale;

    /**
     * List of available locales
     *
     * @var array
     */
    protected $locales = ['ru', 'en'];

    /**
     * Construct
     *
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     * @param \Symfony\Component\Filesystem\Filesystem $fs
     * @param string $locale
     */
    public function __construct(Registry $doctrine, Filesystem $fs, $locale)
    {
        $this->em = $doctrine->getManager();
        $this->fs = $fs;
        $this->rep = $this->em->getRepository('AnimeDbAppBundle:Plugin');
        $this->locale = substr($locale, 0, 2);
        $this->locale = in_array($this->locale, $this->locales) ? $locale : self::API_DEFAULT_LOCALE;
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