<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Util;

use AnimeDb\Bundle\AppBundle\Util\Pagination;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Page;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Previous;

/**
 * Test pagination
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class PaginationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Pagination
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination
     */
    protected $pagination;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->pagination = new Pagination();
    }

    /**
     * Get navigation
     *
     * @return array
     */
    public function getNavigation()
    {
        return [
            [
                1,
                1,
                5,
                '%s',
                null,
                [
                    'total' => 1,
                    'current' => 1,
                    'max_navigate' => 5,
                    'list' => []
                ]
            ],
            [
                2,
                1,
                5,
                '/?page=%s',
                null,
                [
                    'total' => 2,
                    'current' => 1,
                    'max_navigate' => 5,
                    'list' => [
                        (new Current())
                            ->setName(1)
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Page())
                            ->setName(2)
                            ->setPage(2)
                            ->setLink('/?page=2'),
                        (new Next())
                            ->setPage(2)
                            ->setLink('/?page=2'),
                        (new Last())
                            ->setPage(2)
                            ->setLink('/?page=2')
                    ]
                ]
            ],
            [
                2,
                2,
                5,
                '/?page=%s',
                null,
                [
                    'total' => 2,
                    'current' => 2,
                    'max_navigate' => 5,
                    'list' => [
                        (new First())
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Previous())
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Page())
                            ->setName(1)
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Current())
                            ->setName(2)
                            ->setPage(2)
                            ->setLink('/?page=2')
                    ]
                ]
            ],
            [
                10,
                1,
                5,
                '/?page=%s',
                null,
                [
                    'total' => 10,
                    'current' => 1,
                    'max_navigate' => 5,
                    'list' => [
                        (new Current())
                            ->setName(1)
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Page())
                            ->setName(2)
                            ->setPage(2)
                            ->setLink('/?page=2'),
                        (new Page())
                            ->setName(3)
                            ->setPage(3)
                            ->setLink('/?page=3'),
                        (new Page())
                            ->setName(4)
                            ->setPage(4)
                            ->setLink('/?page=4'),
                        (new Page())
                            ->setName(5)
                            ->setPage(5)
                            ->setLink('/?page=5'),
                        (new Next())
                            ->setPage(2)
                            ->setLink('/?page=2'),
                        (new Last())
                            ->setPage(10)
                            ->setLink('/?page=10')
                    ]
                ]
            ],
            [
                10,
                10,
                5,
                '/?page=%s',
                null,
                [
                    'total' => 10,
                    'current' => 10,
                    'max_navigate' => 5,
                    'list' => [
                        (new First())
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Previous())
                            ->setPage(9)
                            ->setLink('/?page=9'),
                        (new Page())
                            ->setName(6)
                            ->setPage(6)
                            ->setLink('/?page=6'),
                        (new Page())
                            ->setName(7)
                            ->setPage(7)
                            ->setLink('/?page=7'),
                        (new Page())
                            ->setName(8)
                            ->setPage(8)
                            ->setLink('/?page=8'),
                        (new Page())
                            ->setName(9)
                            ->setPage(9)
                            ->setLink('/?page=9'),
                        (new Current())
                            ->setName(10)
                            ->setPage(10)
                            ->setLink('/?page=10')
                    ]
                ]
            ],
            [
                10,
                5,
                5,
                '/?page=%s',
                null,
                [
                    'total' => 10,
                    'current' => 5,
                    'max_navigate' => 5,
                    'list' => [
                        (new First())
                            ->setPage(1)
                            ->setLink('/?page=1'),
                        (new Previous())
                            ->setPage(4)
                            ->setLink('/?page=4'),
                        (new Page())
                            ->setName(3)
                            ->setPage(3)
                            ->setLink('/?page=3'),
                        (new Page())
                            ->setName(4)
                            ->setPage(4)
                            ->setLink('/?page=4'),
                        (new Current())
                            ->setName(5)
                            ->setPage(5)
                            ->setLink('/?page=5'),
                        (new Page())
                            ->setName(6)
                            ->setPage(6)
                            ->setLink('/?page=6'),
                        (new Page())
                            ->setName(7)
                            ->setPage(7)
                            ->setLink('/?page=7'),
                        (new Next())
                            ->setPage(6)
                            ->setLink('/?page=6'),
                        (new Last())
                            ->setPage(10)
                            ->setLink('/?page=10')
                    ]
                ]
            ],
            [
                10,
                5,
                4,
                function ($number) {
                    return sprintf('/?page=%s', $number);
                },
                '/',
                [
                    'total' => 10,
                    'current' => 5,
                    'max_navigate' => 4,
                    'list' => [
                        (new First())
                            ->setPage(1)
                            ->setLink('/'),
                        (new Previous())
                            ->setPage(4)
                            ->setLink('/?page=4'),
                        (new Page())
                            ->setName(4)
                            ->setPage(4)
                            ->setLink('/?page=4'),
                        (new Current())
                            ->setName(5)
                            ->setPage(5)
                            ->setLink('/?page=5'),
                        (new Page())
                            ->setName(6)
                            ->setPage(6)
                            ->setLink('/?page=6'),
                        (new Page())
                            ->setName(7)
                            ->setPage(7)
                            ->setLink('/?page=7'),
                        (new Next())
                            ->setPage(6)
                            ->setLink('/?page=6'),
                        (new Last())
                            ->setPage(10)
                            ->setLink('/?page=10')
                    ]
                ]
            ]
        ];
    }

    /**
     * Test create navigation
     *
     * @dataProvider getNavigation
     *
     * @param integer $total
     * @param integer $current_page
     * @param integer $max_navigate
     * @param string|\Closure $link
     * @param string $ferst_page_link
     * @param array $expected
     */
    public function testCreateNavigation(
        $total,
        $current_page,
        $max_navigate,
        $link,
        $ferst_page_link,
        array $expected
    ) {
        $this->assertEquals($expected, $this->pagination->createNavigation(
            $total,
            $current_page,
            $max_navigate,
            $link,
            $ferst_page_link
        ));
    }
}
