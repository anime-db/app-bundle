<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Entity;

use AnimeDb\Bundle\AppBundle\Entity\Field\Image;
use AnimeDb\Bundle\AppBundle\Entity\Notice;
use AnimeDb\Bundle\AppBundle\Entity\Plugin;
use AnimeDb\Bundle\AppBundle\Entity\Task;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Image
     */
    public $image;

    /**
     * @var Notice
     */
    public $notice;

    /**
     * @var Plugin
     */
    public $plugin;

    /**
     * @var Task
     */
    public $task;

    protected function setUp()
    {
        $this->image = new Image();
        $this->notice = new Notice();
        $this->plugin = new Plugin();
        $this->task = new Task();
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return [
            // Image
            ['image', 'getRemote', 'setRemote'],
            // Notice
            ['notice', 'getMessage', 'setMessage'],
            ['notice', 'getLifetime', 'setLifetime', Notice::DEFAULT_LIFETIME, 100500],
            ['notice', 'getStatus', 'setStatus', Notice::STATUS_CREATED, Notice::STATUS_SHOWN],
            ['notice', 'getType', 'setType', Notice::DEFAULT_TYPE],
            // Plugin
            ['plugin', 'getName', 'setName'],
            ['plugin', 'getTitle', 'setTitle'],
            ['plugin', 'getDescription', 'setDescription'],
            // Task
            ['task', 'getCommand', 'setCommand'],
            ['task', 'getModify', 'setModify'],
            ['task', 'getStatus', 'setStatus', Task::STATUS_DISABLED, Task::STATUS_ENABLED]
        ];
    }

    /**
     * @dataProvider getMethods
     * 
     * @param string $entity
     * @param string $getter
     * @param string $setter
     * @param mixed $default
     * @param mixed $new
     */
    public function testGetSet($entity, $getter, $setter, $default = '', $new = 'foo')
    {
        $this->assertEquals($default, call_user_func([$this->$entity, $getter]));
        $this->assertEquals($this->$entity, call_user_func([$this->$entity, $setter], $new));
        $this->assertEquals($new, call_user_func([$this->$entity, $getter]));
    }

    /**
     * @return array
     */
    public function getMethodsTime()
    {
        $now = new \DateTime();
        return [
            // Notice
            ['notice', 'getDateClosed', 'setDateClosed'],
            ['notice', 'getDateStart', 'setDateStart', $now],
            // Plugin
            ['plugin', 'getDateInstall', 'setDateInstall', $now],
            // Task
            ['task', 'getLastRun', 'setLastRun'],
            ['task', 'getNextRun', 'setNextRun', $now]
        ];
    }

    /**
     * @dataProvider getMethodsTime
     * 
     * @param string $entity
     * @param string $getter
     * @param string $setter
     * @param mixed $default
     */
    public function testGetSetTime($entity, $getter, $setter, $default = null)
    {
        $new = (new \DateTime())->modify('+100 seconds');
        if ($default) {
            $this->assertInstanceOf('\DateTime', call_user_func([$this->$entity, $getter]));
        } else {
            $this->assertNull(call_user_func([$this->$entity, $getter]));
        }
        $this->assertEquals($this->$entity, call_user_func([$this->$entity, $setter], $new));
        $this->assertEquals($new, call_user_func([$this->$entity, $getter]));
    }
}
