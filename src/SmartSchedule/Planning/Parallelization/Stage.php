<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use Munus\Collection\Set;

final class Stage
{
    /**
     * @param Set<Stage>        $dependencies
     * @param Set<ResourceName> $resources
     */
    public function __construct(
        private string $stageName,
        private Set $dependencies,
        private Set $resources,
        private \DateInterval $duration
    ) {
    }

    public static function of(string $stageName): self
    {
        return new self($stageName, Set::empty(), Set::empty(), new \DateInterval('P0D'));
    }

    public function dependsOn(Stage $stage): self
    {
        $this->dependencies = $this->dependencies->add($stage);

        return $this;
    }

    public function name(): string
    {
        return $this->stageName;
    }

    /**
     * @return Set<ResourceName>
     */
    public function resources(): Set
    {
        return $this->resources;
    }

    public function duration(): \DateInterval
    {
        return $this->duration;
    }

    /**
     * @return Set<Stage>
     */
    public function dependencies(): Set
    {
        return $this->dependencies;
    }
}
