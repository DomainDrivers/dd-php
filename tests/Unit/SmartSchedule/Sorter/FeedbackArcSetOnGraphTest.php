<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Sorter;

use DomainDrivers\SmartSchedule\Sorter\Edge;
use DomainDrivers\SmartSchedule\Sorter\FeedbackArcSetOnGraph;
use DomainDrivers\SmartSchedule\Sorter\Node;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FeedbackArcSetOnGraph::class)]
final class FeedbackArcSetOnGraphTest extends TestCase
{
    #[Test]
    public function canFindMinimumNumberOfEdgesToRemoveToMakeTheGraphAcyclic(): void
    {
        // given
        $node1 = Node::with('1');
        $node2 = Node::with('2');
        $node3 = Node::with('3');
        $node4 = Node::with('4');
        $node3 = $node3->dependsOn($node1);
        $node4 = $node4->dependsOn($node3);
        $node1 = $node1->dependsOn($node4);
        $node2 = $node2->dependsOn($node3);
        $node1 = $node1->dependsOn($node2);

        $toRemove = FeedbackArcSetOnGraph::calculate(GenericList::of($node1, $node2, $node3, $node4));

        self::assertTrue($toRemove->containsAll(Set::of(new Edge(3, 1), new Edge(4, 3))));
    }

    #[Test]
    public function whenGraphIsAcyclicThereIsNothingToRemove(): void
    {
        // given
        $node1 = Node::with('1');
        $node2 = Node::with('2');
        $node3 = Node::with('3');
        $node4 = Node::with('4');
        $node2 = $node2->dependsOn($node3);
        $node1 = $node1->dependsOn($node2);
        $node1 = $node1->dependsOn($node4);

        $toRemove = FeedbackArcSetOnGraph::calculate(GenericList::of($node1, $node2, $node3, $node4));

        self::assertTrue($toRemove->isEmpty());
    }
}
