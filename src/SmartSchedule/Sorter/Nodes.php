<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Collection\Set;
use Munus\Collection\Traversable;

final readonly class Nodes
{
    /**
     * @param Set<Node> $nodes
     */
    public function __construct(private Set $nodes)
    {
    }

    public static function of(Node ...$nodes): self
    {
        return new self(Set::ofAll($nodes));
    }

    public static function empty(): self
    {
        return new self(Set::empty());
    }

    /**
     * @return Set<Node>
     */
    public function all(): Set
    {
        return $this->nodes;
    }

    public function add(Node $node): self
    {
        return new self($this->nodes->add($node));
    }

    /**
     * @param Traversable<Node> $nodes
     */
    public function withAllDependenciesPresentIn(Traversable $nodes): self
    {
        return new self($this->nodes
            ->filter(fn (Node $n) => $nodes->containsAll($n->dependencies->all()))
        );
    }

    /**
     * @param Set<Node> $nodes
     */
    public function removeAll(Set $nodes): self
    {
        return new self($this->nodes
            ->filter(fn (Node $s) => !$nodes->contains($s))
        );
    }
}
