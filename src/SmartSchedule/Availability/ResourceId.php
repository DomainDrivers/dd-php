<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use Symfony\Component\Uid\Uuid;

final class ResourceId implements \Stringable
{
    private function __construct(public ?Uuid $id)
    {
    }

    public static function newOne(): self
    {
        return new self(Uuid::v7());
    }

    public static function none(): self
    {
        return new self(null);
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function getId(): Uuid
    {
        return $this->id ?? throw new \RuntimeException('ResourceId not set');
    }

    public function toString(): ?string
    {
        return $this->id?->toRfc4122();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->toString() ?? '';
    }
}
