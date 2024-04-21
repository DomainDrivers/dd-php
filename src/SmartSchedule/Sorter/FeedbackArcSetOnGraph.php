<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Collection\GenericList;
use Munus\Collection\Map;

final class FeedbackArcSetOnGraph
{
    /**
     * @param GenericList<Node<string>> $initialNodes
     *
     * @return GenericList<Edge>
     */
    public static function calculate(GenericList $initialNodes): GenericList
    {
        $adjacencyList = self::createAdjacencyList($initialNodes);
        $feedbackEdges = GenericList::empty();
        $visited = [$adjacencyList->length() + 1 => null];
        /** @var string $i */
        foreach ($adjacencyList->keys()->toArray() as $i) {
            /** @var array<int, int> $neighbours */
            $neighbours = $adjacencyList->get($i)->get()->toArray();
            if ($neighbours !== []) {
                $visited[(int) $i] = 1;
                for ($j = 0; $j < count($neighbours); ++$j) {
                    if (($visited[$neighbours[$j]] ?? null) === 1) {
                        $feedbackEdges = $feedbackEdges->append(new Edge((int) $i, $neighbours[$j]));
                    } else {
                        $visited[$neighbours[$j]] = 1;
                    }
                }
            }
        }

        return $feedbackEdges;
    }

    /**
     * @param GenericList<Node<string>> $initialNodes
     *
     * @return Map<string, GenericList<int>>
     */
    private static function createAdjacencyList(GenericList $initialNodes): Map
    {
        $adjacencyList = Map::empty();

        $initialNodesArray = $initialNodes->toArray();
        for ($i = 0; $i < $initialNodes->length(); ++$i) {
            $dependencies = GenericList::empty();
            foreach ($initialNodesArray[$i]->dependencies->all() as $dependency) {
                /** @var int $key */
                $key = array_search($dependency, $initialNodesArray, true);
                $dependencies = $dependencies->append($key + 1);
            }
            $adjacencyList = $adjacencyList->put((string) ($i + 1), $dependencies);
        }

        return $adjacencyList;
    }
}
