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

use Symfony\Component\Process\PhpExecutableFinder;
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
     * Path to php executable
     *
     * @var string|null
     */
    private $php_path;

    /**
     * Kernel
     *
     * @var \AppKernel
     */
    protected $kernal;

    /**
     * Console
     *
     * @var string
     */
    protected $console;

    /**
     * Construct
     *
     * @param \AppKernel $kernal
     * @param string $root
     */
    public function __construct(\AppKernel $kernal, $root)
    {
        $this->kernal = $kernal;
        $this->console = escapeshellarg($root).'/console';
    }

    /**
     * Clear cache
     *
     * @param string|null $env
     */
    public function clear($env = null)
    {
        $this->executeCommand('cache:clear --no-debug --env='.($env ?: $this->kernal->getEnvironment()));
    }

    /**
     * Execute command
     *
     * @param string $cmd
     * @param integer $timeout
     */
    protected function executeCommand($cmd, $timeout = 300)
    {
        $process = new Process($this->getPhp().' '.$this->console.' '.$cmd, null, null, null, $timeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', $cmd));
        }
    }

    /**
     * Get path to php executable
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    protected function getPhp()
    {
        if (!$this->php_path) {
            $finder = new PhpExecutableFinder();
            if (!($this->php_path = $finder->find())) {
                throw new \RuntimeException(
                    'The php executable could not be found, add it to your PATH environment variable and try again'
                );
            }
            $this->php_path = escapeshellarg($this->php_path);
        }
        return $this->php_path;
    }
}