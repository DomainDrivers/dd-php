<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\Tools\ToolEvents;
use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashFlowFacade;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashflowRepository;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Infrastructure\OrmCashflowRepository;
use DomainDrivers\SmartSchedule\Allocation\Infrastructure\OrmProjectAllocationsRepository;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsRepository;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Infrastructure\DbalResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Planning\Infrastructure\OrmProjectRepository;
use DomainDrivers\SmartSchedule\Planning\Parallelization\StageParallelization;
use DomainDrivers\SmartSchedule\Planning\PlanChosenResources;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectRepository;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\FixSchemaListener;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(NativeClock::class);
    $services->alias(ClockInterface::class, NativeClock::class);

    $services->set(StageParallelization::class);

    $services->set(PlanChosenResources::class);

    $services->set(AvailabilityFacade::class)
        ->public();

    $services->set(OrmProjectRepository::class);
    $services->alias(ProjectRepository::class, OrmProjectRepository::class);

    $services->set(PlanningFacade::class)
        ->public();

    $services->set(OrmProjectAllocationsRepository::class);
    $services->alias(ProjectAllocationsRepository::class, OrmProjectAllocationsRepository::class);

    $services->set(AllocationFacade::class)
        ->public();

    $services->set(OrmCashflowRepository::class);
    $services->alias(CashflowRepository::class, OrmCashflowRepository::class);

    $services->set(CashFlowFacade::class)
        ->public();

    $services->set(DbalResourceAvailabilityRepository::class);
    $services->alias(ResourceAvailabilityRepository::class, DbalResourceAvailabilityRepository::class);

    if (in_array($configurator->env(), ['dev', 'test'], true)) {
        $services->set(FixSchemaListener::class)
            ->arg('$dependencyFactory', service('doctrine.migrations.dependency_factory'))
            ->tag('doctrine.event_listener', ['event' => ToolEvents::postGenerateSchema]);
    }
};
