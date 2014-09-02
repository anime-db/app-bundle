<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Service;

use AnimeDb\Bundle\AppBundle\Service\PhpFinder;
use Symfony\Component\Process\Process;

/**
 * Cache clearer
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class CacheClearer
{
    /**
     * Environment
     *
     * @var string
     */
    protected $env;

    /**
     * Console
     *
     * @var string
     */
    protected $console;

    /**
     * Php finder
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\PhpFinder
     */
    protected $finder;

    /**
     * Construct
     *
     * @param \AnimeDb\Bundle\AppBundle\Service\PhpFinder $finder
     * @param string $env
     * @param string $root
     */
    public function __construct($finder, $env, $root)
    {
        $this->finder = $finder;
        $this->env = $env;
        $this->console = escapeshellarg($root).'/console';
    }

    /**
     * Clear cache
     *
     * @param string|null $env
     */
    public function clear($env = null)
    {
        $this->executeCommand('cache:clear --no-debug --env='.($env ?: $this->env));
    }

    /**
     * Execute command
     *
     * @param string $cmd
     * @param integer $timeout
     */
    protected function executeCommand($cmd, $timeout = 300)
    {
        $process = new Process($this->finder->getPath().' '.$this->console.' '.$cmd, null, null, null, $timeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', $cmd));
        }
    }
}