<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Sorter\Node;
use DomainDrivers\SmartSchedule\Sorter\Nodes;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;

final readonly class StagesToNodes
{
    /**
     * @param GenericList<Stage> $stages
     */
    public function calculate(GenericList $stages): Nodes
    {
        $result = $stages->toStream()->collect(Collectors::toMap(
            fn (Stage $stage): string => $stage->name(),
            fn (Stage $stage): Node => Node::with($stage->name(), $stage)
        ));

        $stagesArray = $stages->toArray();
        for ($i = 0; $i < $stages->length(); ++$i) {
            $stage = $stagesArray[$i];
            $result = $this->explicitDependencies($stage, $result);
            $result = $this->sharedResources($stage, $stages->toStream()->drop($i + 1)->collect(Collectors::toList()), $result);
        }

        return new Nodes($result->values()->collect(Collectors::toSet()));
    }

    /**
     * @param GenericList<Stage> $with
     * @param Map<string, Node>  $result
     *
     * @return Map<string, Node>
     */
    private function sharedResources(Stage $stage, GenericList $with, Map $result): Map
    {
        foreach ($with as $other) {
            if ($stage->name() !== $other->name()) {
                if (!$stage->resources()->disjoint($other->resources())) {
                    if ($other->resources()->length() > $stage->resources()->length()) {
                        $node = $result->get($stage->name())->get();
                        $node = $node->dependsOn($result->get($other->name())->get());
                        $result = $result->put($stage->name(), $node);
                    } else {
                        $node = $result->get($other->name())->get();
                        $node = $node->dependsOn($result->get($stage->name())->get());
                        $result = $result->put($other->name(), $node);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param Map<string, Node> $result
     *
     * @return Map<string, Node>
     */
    private function explicitDependencies(Stage $stage, Map $result): Map
    {
        /** @var Node $nodeWithExplicitDeps */
        $nodeWithExplicitDeps = $result->get($stage->name())->get();
        foreach ($stage->dependencies() as $explicitDependency) {
            $nodeWithExplicitDeps = $nodeWithExplicitDeps->dependsOn($result->get($explicitDependency->name())->get());
        }

        return $result->put($stage->name(), $nodeWithExplicitDeps);
    }
}
