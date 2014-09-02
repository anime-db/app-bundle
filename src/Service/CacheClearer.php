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

use AnimeDb\Bundle\AppBundle\Service\CommandExecutor;

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
     * Command executor
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\CommandExecutor
     */
    protected $executor;

    /**
     * Construct
     *
     * @param \AnimeDb\Bundle\AppBundle\Service\CommandExecutor $executor
     * @param string $env
     */
    public function __construct(CommandExecutor $executor, $env)
    {
        $this->executor = $executor;
        $this->env = $env;
    }

    /**
     * Clear cache
     *
     * @param string $env
     */
    public function clear($env = '')
    {
        $this->executor->console('cache:clear --no-debug --env='.($env ?: $this->env));
    }
}