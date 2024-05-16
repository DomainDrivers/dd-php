<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Capability;

use Munus\Collection\Set;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;
use Munus\Value\Comparable;

final readonly class Capability implements Comparable
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
     * @return Set<self>
     */
    public static function skills(string ...$skills): Set
    {
        return Stream::ofAll($skills)->map(fn (string $s) => self::skill($s))->collect(Collectors::toSet());
    }

    /**
     * @return Set<self>
     */
    public static function assets(string ...$assets): Set
    {
        return Stream::ofAll($assets)->map(fn (string $a) => self::asset($a))->collect(Collectors::toSet());
    }

    /**
     * @return Set<self>
     */
    public static function permissions(string ...$permissions): Set
    {
        return Stream::ofAll($permissions)->map(fn (string $p) => self::permission($p))->collect(Collectors::toSet());
    }

    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    public function equals(Comparable $other): bool
    {
        return self::class === $other::class && $this->name === $other->name && $this->type === $other->type;
    }
}
