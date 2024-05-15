<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\RuleBuilders\Architecture\Architecture;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $layeredArchitectureRules = Architecture::withComponents()
        ->component('Availability')->definedBy('DomainDrivers\SmartSchedule\Planning\Availability\*')
        ->component('Allocation')->definedBy('DomainDrivers\SmartSchedule\Allocation\*')
        ->component('Cashflow')->definedBy('DomainDrivers\SmartSchedule\Allocation\Cashflow\*')
        ->component('Parallelization')->definedBy('DomainDrivers\SmartSchedule\Planning\Parallelization\*')
        ->component('Sorter')->definedBy('DomainDrivers\SmartSchedule\Sorter\*')
        ->component('Simulation')->definedBy('DomainDrivers\SmartSchedule\Simulation\*')
        ->component('Optimization')->definedBy('DomainDrivers\SmartSchedule\Optimization\*')
        ->component('Shared')->definedBy('DomainDrivers\SmartSchedule\Shared\*')

        ->where('Availability')->mayDependOnComponents('Shared')
        ->where('Allocation')->mayDependOnComponents('Shared', 'Availability', 'Cashflow', 'Simulation', 'Optimization')
        ->where('Parallelization')->mayDependOnComponents('Sorter', 'Shared', 'Availability')
        ->where('Sorter')->shouldNotDependOnAnyComponent()
        ->where('Simulation')->mayDependOnComponents('Optimization', 'Shared')
        ->where('Optimization')->mayDependOnComponents('Shared')
        ->where('Shared')->shouldNotDependOnAnyComponent()

        ->rules();

    $config
        ->add($classSet, ...$layeredArchitectureRules);
};
