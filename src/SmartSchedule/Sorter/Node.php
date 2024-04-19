<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;

final readonly class Node
{
    public function __construct(
        public string $name,
        public Nodes $dependencies,
        public ?Stage $content = null
    ) {
    }

    public static function with(string $name, Stage $content = null): self
    {
        return new self($name, Nodes::empty(), $content);
    }

    public function dependsOn(Node $node): self
    {
        return new self($this->name, $this->dependencies->add($node), $this->content);
    }
}
