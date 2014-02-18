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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Command executor
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class Command
{
    /**
     * Host
     *
     * @var string
     */
    protected $host;

    /**
     * Path
     *
     * @var string
     */
    protected $path;

    /**
     * Connect timeout
     *
     * @var integer
     */
    const TIMEOUT = 2;

    /**
     * Construct
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function __construct(Request $request, RouterInterface $router)
    {
        $this->host = $request->getHost().':'.$request->getPort();
        $this->path = $router->generate('command_exec');
    }

    /**
     * Execute command
     *
     * @param string $command
     */
    public function exec($command)
    {
        if (!$command) {
            throw new \InvalidArgumentException('Unknown command');
        }

        $fp = fsockopen($this->host, 80, $errno, $errstr, self::TIMEOUT);
        $out = "POST ".$this->path." HTTP/1.1\r\n";
        $out .= "Host: ".$this->host."\r\n";
        $out .= "Connection: Close\r\n\r\n";
        $out .= "command=".urlencode($command)."\r\n";
        fwrite($fp, $out);
        fclose($fp);
    }
}