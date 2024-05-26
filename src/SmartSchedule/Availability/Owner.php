<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use Symfony\Component\Uid\Uuid;

final readonly class Owner implements \Stringable
{
    private function __construct(public ?Uuid $id)
    {
    }

    public static function newOne(): self
    {
        return new self(Uuid::v7());
    }

    public static function of(Uuid $id): self
    {
        return new self($id);
    }

    public static function none(): self
    {
        return new self(null);
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function byNone(): bool
    {
        return $this->id === null;
    }

    public function toString(): ?string
    {
        return $this->id?->toRfc4122();
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function getId(): Uuid
    {
        return $this->id ?? throw new \RuntimeException('OwnerId not set');
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->toString() ?? '';
    }
}
