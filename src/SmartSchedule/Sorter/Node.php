<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use Munus\Value\Comparable;

/**
 * @template T
 */
final readonly class Node implements Comparable
{
    /**
     * @param Nodes<T> $dependencies
     * @param T        $content
     */
    public function __construct(
        public string $name,
        public Nodes $dependencies,
        public mixed $content = null
    ) {
    }

    /**
     * @param T $content
     *
     * @return self<T>
     */
    public static function with(string $name, $content = null): self
    {
        /** @var self<T> $node */
        $node = new self($name, Nodes::empty(), $content);

        return $node;
    }

    /**
     * @param Node<T> $node
     *
     * @return self<T>
     */
    public function dependsOn(Node $node): self
    {
        return new self($this->name, $this->dependencies->add($node), $this->content);
    }

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class && $this->name === $other->name;
    }
}
