<?php
/**
 * AnimeDb package
 *
 * @package   AnimeDb
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2011, Peter Gribanov
 * @license   http://opensource.org/licenses/GPL-3.0 GPL v3
 */

namespace AnimeDb\Bundle\AppBundle\Tests\Util\Pagination;

use AnimeDb\Bundle\AppBundle\Util\Pagination;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Page;
use AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Previous;

/**
 * Test node
 *
 * @package AnimeDb\Bundle\AppBundle\Tests\Util\Pagination
 * @author  Peter Gribanov <info@peter-gribanov.ru>
 */
class NodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Node
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node
     */
    protected $node;

    /**
     * Current
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Current
     */
    protected $current;

    /**
     * First
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\First
     */
    protected $first;

    /**
     * Last
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Last
     */
    protected $last;

    /**
     * Next
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Next
     */
    protected $next;

    /**
     * Page
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Page
     */
    protected $page;

    /**
     * Previous
     *
     * @var \AnimeDb\Bundle\AppBundle\Util\Pagination\Node\Previous
     */
    protected $prev;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->node = new Node();
        $this->current = new Current();
        $this->first = new First();
        $this->last = new Last();
        $this->next = new Next();
        $this->page = new Page();
        $this->prev = new Previous();
    }

    /**
     * Get methods
     *
     * @return array
     */
    public function getMethods()
    {
        $this->setUp();
        $link = 'http://example.com/';
        $result = [];
        $nodes = [
            $this->current,
            $this->first,
            $this->last,
            $this->next,
            $this->node,
            $this->page,
            $this->prev
        ];
        // set default methods
        foreach ($nodes as $node) {
            $result[] = [
                '',
                $link,
                [$node, 'getLink'],
                [$node, 'setLink']
            ];
            $result[] = [
                1,
                5,
                [$node, 'getPage'],
                [$node, 'setPage']
            ];
            $result[] = [
                $node == $this->current,
                $node != $this->current,
                [$node, 'isCurrent'],
                [$node, 'setCurrent']
            ];
        }

        // test names
        $names = [
            'current' => '',
            'first' => '‹‹',
            'last' => '››',
            'next' => '→',
            'node' => '',
            'page' => '',
            'prev' => '←'
        ];
        foreach ($names as $node => $default) {
            $result[] = [
                $default,
                'foo',
                [$this->$node, 'getName'],
                [$this->$node, 'setName']
            ];
        }

        // test titles
        $titles = [
            'current' => 'Current page',
            'first' => 'Go to the first page',
            'last' => 'Go to the last page',
            'next' => 'Go to the next page',
            'node' => '',
            'page' => 'Go to page number: %page%',
            'prev' => 'Go to the previous page'
        ];
        foreach ($titles as $node => $default) {
            $result[] = [
                $default,
                'foo',
                [$this->$node, 'getTitle'],
                [$this->$node, 'setTitle']
            ];
        }
        return $result;
    }

    /**
     * Test get set
     *
     * @dataProvider getMethods
     *
     * @param mixed $default
     * @param mixed $new
     * @param callback $getter
     * @param callback $setter
     */
    public function testGetSet($default, $new, $getter, $setter) {
        $this->assertEquals($default, call_user_func($getter));
        call_user_func($setter, $new);
        $this->assertEquals($new, call_user_func($getter));
    }

    /**
     * Get node types
     *
     * @return array
     */
    public function getNodeTypes()
    {
        $this->setUp();
        return [
            [Pagination::TYPE_CURENT, $this->current],
            [Pagination::TYPE_FIRST, $this->first],
            [Pagination::TYPE_LAST, $this->last],
            [Pagination::TYPE_NEXT, $this->next],
            [Pagination::TYPE_PAGE, $this->page],
            [Pagination::TYPE_PAGE, $this->node],
            [Pagination::TYPE_PREV, $this->prev]
        ];
    }

    /**
     * Test get type
     *
     * @dataProvider getNodeTypes
     *
     * @param string $type
     * @param \AnimeDb\Bundle\AppBundle\Util\Pagination\Node $node
     */
    public function testGetType($type, Node $node) {
        $this->assertEquals($type, $node->getType());
    }
}
