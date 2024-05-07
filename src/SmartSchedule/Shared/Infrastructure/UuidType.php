<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

abstract class UuidType extends Type
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

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    abstract protected function fromString(string $value): \Stringable;
}
