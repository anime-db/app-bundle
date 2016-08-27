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
use AnimeDb\Bundle\AppBundle\Service\PhpFinder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class CommandExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PhpFinder
     */
    protected $finder;

    /**
     * @var CommandExecutor
     */
    protected $executor;

    /**
     * @var string
     */
    protected $path = 'foo';

    /**
     * @var string
     */
    protected $root_dir = '/tmp';

    protected function setUp()
    {
        $this->finder = $this
            ->getMockBuilder('\AnimeDb\Bundle\AppBundle\Service\PhpFinder')
            ->disableOriginalConstructor()
            ->getMock();

    }

    /**
     * @param boolean $have_request
     *
     * @return CommandExecutor
     */
    protected function getCommandExecutor($have_request = true)
    {
        /* @var $router \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
        $router = $this->getMock('\Symfony\Component\Routing\RouterInterface');
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('command_exec')
            ->will($this->returnValue($this->path));
        if ($have_request) {
            $request = $this
                ->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
                ->disableOriginalConstructor()
                ->getMock();
            $request
                ->expects($this->once())
                ->method('getHost')
                ->will($this->returnValue($this->path));
            $request
                ->expects($this->once())
                ->method('getPort')
                ->will($this->returnValue(56780));
        } else {
            $request = null;
        }

        /* @var $request_stack \PHPUnit_Framework_MockObject_MockObject|RequestStack */
        $request_stack = $this
            ->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $request_stack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->will($this->returnValue($request));

        return new CommandExecutor($this->finder, $router, $request_stack, $this->root_dir);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecFail()
    {
        $this->getCommandExecutor()->exec('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSendFail()
    {
        $this->getCommandExecutor(false)->send('command', '');
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        $console = escapeshellarg($this->root_dir . DIRECTORY_SEPARATOR . 'console');

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $expected = "/path/to/php {$console} cache:clear > nul 2>&1";
        } else {
            $expected = "/path/to/php {$console} cache:clear > /dev/null 2>&1";
        }

        return [
            [
                'php app/console cache:clear > /dev/null 2>&1',
                $expected,
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
            ->will($this->returnValue('/path/to/php'));

        $this->assertEquals($expected, $this->getCommandExecutor()->prepare($command));
    }
}
