<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Capability;

use Munus\Collection\GenericList;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;

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

    /**
     * @return GenericList<self>
     */
    public static function skills(string ...$skills): GenericList
    {
        return Stream::ofAll($skills)->map(fn (string $s) => self::skill($s))->collect(Collectors::toList());
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->type === $other->type;
    }
}
