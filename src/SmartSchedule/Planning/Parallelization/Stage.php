<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use Munus\Collection\Set;

final readonly class Stage
{
    /**
     * @param Set<Stage>      $dependencies
     * @param Set<ResourceId> $resources
     */
    public function __construct(
        private string $stageName,
        private Set $dependencies,
        private Set $resources,
        private Duration $duration
    ) {
    }

    public static function of(string $stageName): self
    {
        return new self($stageName, Set::empty(), Set::empty(), Duration::ofDays(0));
    }

    public function ofDuration(Duration $duration): self
    {
        return new self($this->stageName, $this->dependencies, $this->resources, $duration);
    }

    public function dependsOn(Stage $stage): self
    {
        return new self($this->stageName, $this->dependencies->add($stage), $this->resources, $this->duration);
    }

    public function withChosenResourceCapabilities(ResourceId ...$resources): self
    {
        return new self($this->stageName, $this->dependencies, Set::ofAll($resources), $this->duration);
    }

    public function name(): string
    {
        return $this->stageName;
    }

    /**
     * @return Set<ResourceId>
     */
    public function resources(): Set
    {
        return $this->resources;
    }

    public function duration(): Duration
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
