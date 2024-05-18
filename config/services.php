<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\Tools\ToolEvents;
use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityRepository;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityScheduler;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\Infrastructure\OrmAllocatableCapabilityRepository;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashFlowFacade;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashflowRepository;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Infrastructure\OrmCashflowRepository;
use DomainDrivers\SmartSchedule\Allocation\Infrastructure\OrmProjectAllocationsRepository;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsRepository;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Infrastructure\DbalResourceAvailabilityReadModel;
use DomainDrivers\SmartSchedule\Availability\Infrastructure\DbalResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityReadModel;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Planning\Infrastructure\OrmProjectRepository;
use DomainDrivers\SmartSchedule\Planning\Parallelization\StageParallelization;
use DomainDrivers\SmartSchedule\Planning\PlanChosenResources;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectRepository;
use DomainDrivers\SmartSchedule\Resource\Device\DeviceFacade;
use DomainDrivers\SmartSchedule\Resource\Device\DeviceRepository;
use DomainDrivers\SmartSchedule\Resource\Device\Infrastructure\OrmDeviceRepository;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeRepository;
use DomainDrivers\SmartSchedule\Resource\Employee\Infrastructure\OrmEmployeeRepository;
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

    $services->set(DbalResourceAvailabilityReadModel::class);
    $services->alias(ResourceAvailabilityReadModel::class, DbalResourceAvailabilityReadModel::class);

    $services->set(DbalResourceAvailabilityRepository::class);
    $services->alias(ResourceAvailabilityRepository::class, DbalResourceAvailabilityRepository::class);

    $services->set(OrmDeviceRepository::class);
    $services->alias(DeviceRepository::class, OrmDeviceRepository::class);

    $services->set(DeviceFacade::class)
        ->public();

    $services->set(OrmEmployeeRepository::class);
    $services->alias(EmployeeRepository::class, OrmEmployeeRepository::class);

    $services->set(EmployeeFacade::class)
        ->public();

    $services->set(OrmAllocatableCapabilityRepository::class);
    $services->alias(AllocatableCapabilityRepository::class, OrmAllocatableCapabilityRepository::class);

    $services->set(CapabilityFinder::class)
        ->public();

    $services->set(CapabilityScheduler::class)
        ->public();

    if (in_array($configurator->env(), ['dev', 'test'], true)) {
        $services->set(FixSchemaListener::class)
            ->arg('$dependencyFactory', service('doctrine.migrations.dependency_factory'))
            ->tag('doctrine.event_listener', ['event' => ToolEvents::postGenerateSchema]);
    }
};
