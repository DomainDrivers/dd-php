<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use Munus\Value\Comparable;
use Symfony\Component\Uid\Uuid;

final readonly class RiskPeriodicCheckSagaId implements \Stringable, Comparable
{
    private function __construct(public Uuid $id)
    {
    }

    public static function newOne(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function toString(): string
    {
        return $this->id->toRfc4122();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->toString();
    }

    #[\Override]
    public function equals(Comparable $other): bool
    {
        return self::class === $other::class && $this->id->equals($other->id);
    }
}
