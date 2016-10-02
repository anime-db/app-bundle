<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Service;

use AnimeDb\Bundle\AppBundle\Service\CacheClearer;
use AnimeDb\Bundle\AppBundle\Service\CommandExecutor;

class CacheClearerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getEnv()
    {
        return [
            [''],
            ['prod'],
            ['dev'],
            ['test'],
        ];
    }

    /**
     * @dataProvider getEnv
     *
     * @param string $env
     */
    public function testClear($env)
    {
        /* @var $executor \PHPUnit_Framework_MockObject_MockObject|CommandExecutor */
        $executor = $this
            ->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();
        $executor
            ->expects($this->once())
            ->method('console')
            ->with('cache:clear --no-debug --env='.($env ?: 'dev'), 0);

        $clearer = new CacheClearer($executor, 'dev');
        $clearer->clear($env);
    }
}
