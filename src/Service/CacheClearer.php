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

/**
 * Cache clearer
 *
 * @package AnimeDb\Bundle\AppBundle\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class CacheClearer
{
    /**
     * @var string
     */
    protected $env;

    /**
     * @var CommandExecutor
     */
    protected $executor;

    /**
     * @param CommandExecutor $executor
     * @param string $env
     */
    public function __construct(CommandExecutor $executor, $env)
    {
        $this->executor = $executor;
        $this->env = $env;
    }

    /**
     * @param string $env
     */
    public function clear($env = '')
    {
        $this->executor->console('cache:clear --no-debug --env='.($env ?: $this->env));
    }
}
