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

use AnimeDb\Bundle\AppBundle\Entity\Notice;

/**
 * Test notice for user
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Entity
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class NoticeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Notice
     *
     * @var \AnimeDb\Bundle\AppBundle\Entity\Notice
     */
    protected $notice;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->notice = new Notice();
    }

    /**
     * Test get statuses
     */
    public function testGetStatuses()
    {
        $this->assertEquals(
            [
                Notice::STATUS_CREATED,
                Notice::STATUS_SHOWN,
                Notice::STATUS_CLOSED
            ],
            Notice::getStatuses()
        );
    }

    /**
     * Test shown
     */
    public function testShown()
    {
        $date = new \DateTime();
        $this->notice->setDateClosed($date);

        $this->notice->shown();

        $this->assertEquals($date, $this->notice->getDateClosed());
        $this->assertEquals(Notice::STATUS_SHOWN, $this->notice->getStatus());
    }

    /**
     * Test shown modify date closed
     */
    public function testShownModify()
    {
        $date = (new \DateTime())->modify('+'.$this->notice->getLifetime().' seconds');

        $this->notice->shown();

        $this->assertEquals($date, $this->notice->getDateClosed());
        $this->assertEquals(Notice::STATUS_SHOWN, $this->notice->getStatus());
    }

    /**
     * Test get id
     */
    public function testGetId()
    {
        $this->assertNull($this->notice->getId());
    }

    /**
     * Test get date created
     */
    public function testGetDateCreated()
    {
        $this->assertInstanceOf('\DateTime', $this->notice->getDateCreated());
        $this->assertEquals($this->notice->getDateCreated(), $this->notice->getDateStart());
    }

    /**
     * Get statuses
     *
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
     * Test get and set status
     *
     * @dataProvider getStatuses
     *
     * @param integer $status
     */
    public function testStatus($status)
    {
        $this->assertEquals(Notice::STATUS_CREATED, $this->notice->getStatus());
        $this->assertEquals($this->notice, $this->notice->setStatus($status));
        $this->assertEquals($status, $this->notice->getStatus());
    }
}