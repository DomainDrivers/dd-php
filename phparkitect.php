<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\RuleBuilders\Architecture\Architecture;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $layeredArchitectureRules = Architecture::withComponents()
        ->component('Parallelization')->definedBy('DomainDrivers\SmartSchedule\Planning\Parallelization\*')
        ->component('Sorter')->definedBy('DomainDrivers\SmartSchedule\Sorter\*')
        ->component('Simulation')->definedBy('DomainDrivers\SmartSchedule\Simulation\*')
        ->component('Optimization')->definedBy('DomainDrivers\SmartSchedule\Optimization\*')

        ->where('Parallelization')->mayDependOnComponents('Sorter')
        ->where('Sorter')->shouldNotDependOnAnyComponent()
        ->where('Simulation')->mayDependOnComponents('Optimization')
        ->where('Optimization')->shouldNotDependOnAnyComponent()

        ->rules();

    $config
        ->add($classSet, ...$layeredArchitectureRules);
};
