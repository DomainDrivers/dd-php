<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\Tools\ToolEvents;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Planning\Infrastructure\OrmProjectRepository;
use DomainDrivers\SmartSchedule\Planning\Parallelization\StageParallelization;
use DomainDrivers\SmartSchedule\Planning\PlanChosenResources;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectRepository;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\FixSchemaListener;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(StageParallelization::class);

    $services->set(PlanChosenResources::class);

    $services->set(AvailabilityFacade::class);

    $services->set(OrmProjectRepository::class);
    $services->alias(ProjectRepository::class, OrmProjectRepository::class);

    $services->set(PlanningFacade::class)
        ->public();

    if (in_array($configurator->env(), ['dev', 'test'], true)) {
        $services->set(FixSchemaListener::class)
            ->arg('$dependencyFactory', service('doctrine.migrations.dependency_factory'))
            ->tag('doctrine.event_listener', ['event' => ToolEvents::postGenerateSchema]);
    }
};
