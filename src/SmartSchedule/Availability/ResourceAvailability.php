<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final class ResourceAvailability
{
    public function __construct(
        public readonly ResourceAvailabilityId $id,
        public readonly ResourceId $resourceId,
        public readonly ResourceId $resourceParentId,
        public readonly TimeSlot $segment,
        private Blockade $blockade,
        private int $version = 0
    ) {
    }

    public static function of(
        ResourceAvailabilityId $availabilityId,
        ResourceId $resourceId,
        TimeSlot $segment
    ): self {
        return new self($availabilityId, $resourceId, ResourceId::none(), $segment, Blockade::none());
    }

    public static function withParent(
        ResourceAvailabilityId $availabilityId,
        ResourceId $resourceId,
        ResourceId $resourceParentId,
        TimeSlot $segment
    ): self {
        return new self($availabilityId, $resourceId, $resourceParentId, $segment, Blockade::none());
    }

    public function block(Owner $requester): bool
    {
        if ($this->isAvailableFor($requester)) {
            $this->blockade = Blockade::ownedBy($requester);

            return true;
        }

        return false;
    }

    public function release(Owner $requester): bool
    {
        if ($this->isAvailableFor($requester)) {
            $this->blockade = Blockade::none();

            return true;
        }

        return false;
    }

    public function disable(Owner $requester): bool
    {
        $this->blockade = Blockade::disabledBy($requester);

        return true;
    }

    public function enable(Owner $requester): bool
    {
        if ($this->blockade->canBeTakenBy($requester)) {
            $this->blockade = Blockade::none();

            return true;
        }

        return false;
    }

    public function isDisabled(): bool
    {
        return $this->blockade->disabled;
    }

    public function blockedBy(): Owner
    {
        return $this->blockade->takenBy;
    }

    public function isDisabledBy(Owner $owner): bool
    {
        return $this->blockade->isDisabledBy($owner);
    }

    public function version(): int
    {
        return $this->version;
    }

    private function isAvailableFor(Owner $requester): bool
    {
        return $this->blockade->canBeTakenBy($requester) && !$this->isDisabled();
    }
}
