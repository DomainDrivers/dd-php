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

        ->where('Parallelization')->mayDependOnComponents('Sorter')
        ->where('Sorter')->shouldNotDependOnAnyComponent()

        ->rules();

    $config
        ->add($classSet, ...$layeredArchitectureRules);
};
