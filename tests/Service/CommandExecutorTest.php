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

use AnimeDb\Bundle\AppBundle\Service\CommandExecutor;

/**
 * Test command executor
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Service
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class CommandExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Php finder
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $finder;

    /**
     * Command executor
     *
     * @var \AnimeDb\Bundle\AppBundle\Service\CommandExecutor
     */
    protected $executor;

    /**
     * Command send path
     *
     * @var string
     */
    protected $path = 'foo';

    /**
     * Root dir
     *
     * @var string
     */
    protected $root_dir = '/tmp';

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->finder = $this->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\PhpFinder')
            ->disableOriginalConstructor()
            ->getMock();
        $router = $this->getMock('\Symfony\Component\Routing\RouterInterface');
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('command_exec')
            ->willReturn($this->path);

        $this->executor = new CommandExecutor($this->finder, $router, $this->root_dir);
    }

    /**
     * Test exec fail
     *
     * @expectedException \InvalidArgumentException
     */
    public function testExecFail()
    {
        $this->executor->exec('');
    }

    /**
     * Test send fail
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSendFail()
    {
        $this->executor->send('command', '');
    }

    /**
     * Get commands
     *
     * @return array
     */
    public function getCommands()
    {
        return [
            [
                'php app/console cache:clear > /dev/null 2>&1',
                "/path/to/php '{$this->root_dir}/console' cache:clear > /dev/null 2>&1",
            ],
            [
                'php composer.phar update',
                "/path/to/php composer.phar update",
            ],
            [
                'ping > ping.log',
                'ping > ping.log',
            ]
        ];
    }

    /**
     * Test prepare
     *
     * @dataProvider getCommands
     *
     * @param string $command
     * @param string $expected
     */
    public function testPrepare($command, $expected)
    {
        $this->finder
            ->expects($this->any())
            ->method('getPath')
            ->willReturn('/path/to/php');

        $this->assertEquals($expected, $this->executor->prepare($command));
    }
}