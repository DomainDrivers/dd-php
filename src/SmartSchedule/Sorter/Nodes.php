<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Collection\Set;
use Munus\Collection\Traversable;

/**
 * @template T
 */
final readonly class Nodes
{
    /**
     * @param Set<Node<T>> $nodes
     */
    public function __construct(private Set $nodes)
    {
    }

    /**
     * @param Node<T> ...$nodes
     *
     * @return self<T>
     */
    public static function of(Node ...$nodes): self
    {
        return new self(Set::ofAll($nodes));
    }

    /**
     * @return self<T>
     */
    public static function empty(): self
    {
        return new self(Set::empty());
    }

    /**
     * @return Set<Node<T>>
     */
    public function all(): Set
    {
        return $this->nodes;
    }

    /**
     * @param Node<T> $node
     *
     * @return self<T>
     */
    public function add(Node $node): self
    {
        return new self($this->nodes->add($node));
    }

    /**
     * @param Traversable<Node<T>> $nodes
     *
     * @return self<T>
     */
    public function withAllDependenciesPresentIn(Traversable $nodes): self
    {
        return new self($this->nodes
            ->filter(fn (Node $n) => $nodes->containsAll($n->dependencies->all()))
        );
    }

    /**
     * @param Set<Node<T>> $nodes
     *
     * @return self<T>
     */
    public function removeAll(Set $nodes): self
    {
        return new self($this->nodes
            ->filter(fn (Node $s) => !$nodes->contains($s))
        );
    }
}
