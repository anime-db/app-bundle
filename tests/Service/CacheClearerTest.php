<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Service;

use AnimeDb\Bundle\AppBundle\Service\CacheClearer;

/**
 * Test cache clearer
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class CacheClearerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get env
     *
     * @return array
     */
    public function getEnv()
    {
        return [
            [''],
            ['prod'],
            ['dev'],
            ['test']
        ];
    }

    /**
     * Test clear
     *
     * @dataProvider getEnv
     *
     * @param string $env
     */
    public function testClear($env)
    {
        $executor = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();
        $executor
            ->expects($this->once())
            ->method('console')
            ->with('cache:clear --no-debug --env='.($env ?: 'dev'));

        $clearer = new CacheClearer($executor, 'dev');
        $clearer->clear($env);
    }
}