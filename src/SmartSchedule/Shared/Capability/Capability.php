<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Capability;

final readonly class Capability
{
    public function __construct(public string $name, public string $type)
    {
    }

    public static function skill(string $name): self
    {
        return new self($name, 'SKILL');
    }

    public static function permission(string $name): self
    {
        return new self($name, 'PERMISSION');
    }

    public static function asset(string $name): self
    {
        return new self($name, 'ASSET');
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->type === $other->type;
    }
}
