<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Phpstan;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cashflow;
use DomainDrivers\SmartSchedule\Planning\Project;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

final readonly class ReadWriteProperties implements ReadWritePropertiesExtension
{
    private const array READ_CLASS_LIST = [
        Project::class,
        Cashflow::class,
    ];

    #[\Override]
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return \in_array($property->getDeclaringClass()->getName(), self::READ_CLASS_LIST, true);
    }

    #[\Override]
    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        return \in_array($property->getDeclaringClass()->getName(), self::READ_CLASS_LIST, true);
    }

    #[\Override]
    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
