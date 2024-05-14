<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

final readonly class Blockade
{
    public function __construct(public Owner $takenBy, public bool $disabled)
    {
    }

    public static function none(): self
    {
        return new self(Owner::none(), false);
    }

    public static function disabledBy(Owner $owner): self
    {
        return new self($owner, true);
    }

    public static function ownedBy(Owner $owner): self
    {
        return new self($owner, false);
    }

    public function canBeTakenBy(Owner $requester): bool
    {
        return $this->takenBy->byNone() || $this->takenBy->equals($requester);
    }

    public function isDisabledBy(Owner $owner): bool
    {
        return $this->disabled && $this->takenBy->equals($owner);
    }
}
