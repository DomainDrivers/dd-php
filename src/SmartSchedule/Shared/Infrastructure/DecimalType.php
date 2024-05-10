<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

abstract class DecimalType extends StringType
{
    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        \assert($value instanceof \Stringable);

        return $value->__toString();
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?\Stringable
    {
        if ($value === null) {
            return null;
        }

        \assert(\is_string($value));

        return $this->fromString($value);
    }

    abstract protected function fromString(string $value): \Stringable;
}
