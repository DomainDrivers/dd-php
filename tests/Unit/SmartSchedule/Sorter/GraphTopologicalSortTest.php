<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Sorter;

use DomainDrivers\SmartSchedule\Sorter\GraphTopologicalSort;
use DomainDrivers\SmartSchedule\Sorter\Node;
use DomainDrivers\SmartSchedule\Sorter\Nodes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphTopologicalSort::class)]
final class GraphTopologicalSortTest extends TestCase
{
    private GraphTopologicalSort $graphTopologicalSort;

    protected function setUp(): void
    {
        $this->graphTopologicalSort = new GraphTopologicalSort();
    }

    #[Test]
    public function testTopologicalSortWithSimpleDependencies(): void
    {
        // given
        $node1 = Node::with('Node1');
        $node2 = Node::with('Node2');
        $node3 = Node::with('Node3');
        $node4 = Node::with('Node4');
        $node2 = $node2->dependsOn($node1);
        $node3 = $node3->dependsOn($node1);
        $node4 = $node4->dependsOn($node2);

        // when
        $sortedNodes = $this->graphTopologicalSort->sort(Nodes::of($node1, $node2, $node3, $node4));

        // then
        self::assertSame(3, $sortedNodes->all->length());

        $sortedArray = $sortedNodes->all->toArray();
        self::assertSame(1, $sortedArray[0]->all()->length());
        self::assertTrue($sortedArray[0]->all()->contains($node1));

        self::assertSame(2, $sortedArray[1]->all()->length());
        self::assertTrue($sortedArray[1]->all()->contains($node2));
        self::assertTrue($sortedArray[1]->all()->contains($node3));

        self::assertSame(1, $sortedArray[2]->all()->length());
        self::assertTrue($sortedArray[2]->all()->contains($node4));
    }

    #[Test]
    public function testTopologicalSortWithLinearDependencies(): void
    {
        // given
        $node1 = Node::with('Node1');
        $node2 = Node::with('Node2');
        $node3 = Node::with('Node3');
        $node4 = Node::with('Node4');
        $node5 = Node::with('Node5');
        $node4 = $node4->dependsOn($node5);
        $node3 = $node3->dependsOn($node4);
        $node2 = $node2->dependsOn($node3);
        $node1 = $node1->dependsOn($node2);

        // when
        $sortedNodes = $this->graphTopologicalSort->sort(Nodes::of($node1, $node2, $node3, $node4, $node5));

        // then
        self::assertSame(5, $sortedNodes->all->length());

        $sortedArray = $sortedNodes->all->toArray();
        self::assertSame(1, $sortedArray[0]->all()->length());
        self::assertTrue($sortedArray[0]->all()->contains($node5));

        self::assertSame(1, $sortedArray[1]->all()->length());
        self::assertTrue($sortedArray[1]->all()->contains($node4));

        self::assertSame(1, $sortedArray[2]->all()->length());
        self::assertTrue($sortedArray[2]->all()->contains($node3));

        self::assertSame(1, $sortedArray[3]->all()->length());
        self::assertTrue($sortedArray[3]->all()->contains($node2));

        self::assertSame(1, $sortedArray[4]->all()->length());
        self::assertTrue($sortedArray[4]->all()->contains($node1));
    }

    #[Test]
    public function testNodesWithoutDependencies(): void
    {
        // given
        $node1 = Node::with('Node1');
        $node2 = Node::with('Node2');

        // when
        $sortedNodes = $this->graphTopologicalSort->sort(Nodes::of($node1, $node2));

        // then
        self::assertSame(1, $sortedNodes->all->length());
    }

    #[Test]
    public function testCyclicDependency(): void
    {
        // given
        $node1 = Node::with('Node1');
        $node2 = Node::with('Node2');
        $node2 = $node2->dependsOn($node1);
        $node1 = $node1->dependsOn($node2); // making it cyclic

        // when
        $sortedNodes = $this->graphTopologicalSort->sort(Nodes::of($node1, $node2));

        // then
        self::assertTrue($sortedNodes->all->isEmpty());
    }
}
