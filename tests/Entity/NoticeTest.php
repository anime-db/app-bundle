<?php
/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */
namespace AnimeDb\Bundle\AppBundle\Tests\Entity;

use AnimeDb\Bundle\AppBundle\Entity\Notice;

class NoticeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Notice
     */
    protected $notice;

    protected function setUp()
    {
        $this->notice = new Notice();
    }

    public function testGetStatuses()
    {
        $this->assertEquals(
            [
                Notice::STATUS_CREATED,
                Notice::STATUS_SHOWN,
                Notice::STATUS_CLOSED,
            ],
            Notice::getStatuses()
        );
    }

    public function testShown()
    {
        $date = new \DateTime();
        $this->notice->setDateClosed($date);

        $this->notice->shown();

        $this->assertEquals($date, $this->notice->getDateClosed());
        $this->assertEquals(Notice::STATUS_SHOWN, $this->notice->getStatus());
    }

    public function testShownModify()
    {
        $date = (new \DateTime())->modify('+'.$this->notice->getLifetime().' seconds');

        $this->notice->shown();

        $this->assertEquals($date, $this->notice->getDateClosed());
        $this->assertEquals(Notice::STATUS_SHOWN, $this->notice->getStatus());
    }

    public function testGetId()
    {
        $this->assertNull($this->notice->getId());
    }

    public function testGetDateCreated()
    {
        $this->assertInstanceOf('\DateTime', $this->notice->getDateCreated());
        $this->assertEquals($this->notice->getDateCreated(), $this->notice->getDateStart());
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return [
            [Notice::STATUS_CREATED],
            [Notice::STATUS_SHOWN],
            [Notice::STATUS_CLOSED],
        ];
    }

    /**
     * @dataProvider getStatuses
     *
     * @param int $status
     */
    public function testStatus($status)
    {
        $this->assertEquals(Notice::STATUS_CREATED, $this->notice->getStatus());
        $this->assertEquals($this->notice, $this->notice->setStatus($status));
        $this->assertEquals($status, $this->notice->getStatus());
    }
}
