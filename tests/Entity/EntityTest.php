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

/**
 * Test entity getters and setters
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Image
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Field\Image
     */
    public $image;

    /**
     * Notice
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    public $notice;

    /**
     * Plugin
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Plugin
     */
    public $plugin;

    /**
     * Task
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Task
     */
    public $task;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->image = new Image();
        $this->notice = new Notice();
        $this->plugin = new Plugin();
        $this->task = new Task();
    }

    /**
     * Get methods
     *
     * @return array
     */
    public function getMethods()
    {
        $now = new \DateTime();
        $modify = (new \DateTime())->modify('+100 seconds');
        $file = $this->getMockBuilder('\Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock();
        return [
            // Image
            ['image', 'getRemote', 'setRemote'],
            ['image', 'getLocal', 'setLocal', null, $file],
            // Notice
            ['notice', 'getMessage', 'setMessage'],
            ['notice', 'getDateClosed', 'setDateClosed', null, $now],
            ['notice', 'getDateStart', 'setDateStart', $now, $modify],
            ['notice', 'getLifetime', 'setLifetime', Notice::DEFAULT_LIFETIME, 100500],
            ['notice', 'getStatus', 'setStatus', Notice::STATUS_CREATED, Notice::STATUS_SHOWN],
            ['notice', 'getType', 'setType', Notice::DEFAULT_TYPE],
            // Plugin
            ['plugin', 'getName', 'setName'],
            ['plugin', 'getTitle', 'setTitle'],
            ['plugin', 'getDescription', 'setDescription'],
            ['plugin', 'getLogo', 'setLogo'],
            ['plugin', 'getDateInstall', 'setDateInstall', $now, $modify],
            // Task
            ['task', 'getCommand', 'setCommand'],
            ['task', 'getLastRun', 'setLastRun', null, $modify],
            ['task', 'getNextRun', 'setNextRun', $now, $modify],
            ['task', 'getModify', 'setModify'],
            ['task', 'getStatus', 'setStatus', Task::STATUS_DISABLED, Task::STATUS_ENABLED]
        ];
    }

    /**
     * Test getters and setters
     *
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
}