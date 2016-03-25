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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Process\Process;

/**
 * Command executor
 *
 * Example:
 * <code>
 *   php app/console cache:clear > /dev/null 2>&1
 *   php composer.phar update
 *   ping > ping.log
 * <code>
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class CommandExecutor
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $cwd;

    /**
     * @var string
     */
    protected $console;

    /**
     * @var PhpFinder
     */
    protected $finder;

    /**
     * @var integer
     */
    const TIMEOUT = 2;

    /**
     * @param PhpFinder $finder
     * @param RouterInterface $router
     * @param RequestStack $request_stack
     * @param string $root_dir
     */
    public function __construct(PhpFinder $finder, RouterInterface $router, RequestStack $request_stack, $root_dir)
    {
        $this->finder = $finder;
        $this->cwd = $root_dir.'/../';
        $this->console = escapeshellarg($root_dir.DIRECTORY_SEPARATOR.'console');
        $this->path = $router->generate('command_exec');
        if ($request = $request_stack->getCurrentRequest()) {
            $this->host = $request->getHost().':'.$request->getPort();
        }
    }

    /**
     * @deprecated see self::send()
     *
     * @throws \InvalidArgumentException
     *
     * @param string $command
     */
    public function exec($command)
    {
        if (!$command) {
            throw new \InvalidArgumentException('Unknown command');
        }
        $this->send('php app/console '.$command);
    }

    /**
     * Execute command
     *
     * If timeout <= 0 and callback is null then command will be executed in background
     *
     * @param string $command
     * @param integer $timeout
     * @param callable|null $callback
     */
    public function execute($command, $timeout = 300, $callback = null)
    {
        if ($timeout > 0 || is_callable($callback)) {
            $this->executeCommand($command, $timeout, $callback);
        } else {
            $this->executeCommandInBackground($command);
        }
    }

    /**
     * @param string $command
     * @param integer $timeout
     * @param callable|null $callback
     */
    public function console($command, $timeout = 300, $callback = null)
    {
        $this->execute('php app/console '.$command, $timeout, $callback);
    }

    /**
     * @throws \RuntimeException
     *
     * @param string $command
     * @param integer $timeout
     * @param callable|null $callback
     */
    protected function executeCommand($command, $timeout = 300, $callback = null)
    {
        $process = new Process($this->prepare($command), $this->cwd, null, null, $timeout);
        $process->run($callback);
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', $command));
        }
    }

    /**
     * @param string $command
     */
    protected function executeCommandInBackground($command)
    {
        $cwd = getcwd();
        chdir($this->cwd);

        $command = $this->prepare($command);
        if (defined('PHP_WINDOWS_VERSION_BUILD') && function_exists('popen')) {
            pclose(popen('start /b call '.$command, 'r'));
        } else {
            exec($command.' &');
        }

        chdir($cwd);
    }

    /**
     * Send the command to perform in a new thread
     *
     * @param string $command
     * @param string $host
     */
    public function send($command, $host = '')
    {
        $host = $host ?: $this->host;
        if (!$host) {
            throw new \InvalidArgumentException('Unknown host that will run the command');
        }
        $content = 'command='.urlencode($command);

        $fp = fsockopen($this->host, 80, $errno, $errstr, self::TIMEOUT);
        $request  = "POST ".$this->path." HTTP/1.1\r\n";
        $request .= "Host: ".$host."\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: ".strlen($content)."\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $request .= $content;
        fwrite($fp, $request);
        fclose($fp);
    }

    /**
     * @param string $command
     *
     * @return string
     */
    public function prepare($command)
    {
        // change path for php
        if (substr($command, 0, 4) == 'php ') {
            $command = $this->finder->getPath().substr($command, 3);
        }

        // change path to console
        $command = str_replace(' app/console ', ' '.$this->console.' ', $command);

        // change /dev/null for Windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $command = str_replace('/dev/null', 'nul', $command);
        }

        return $command;
    }
}
