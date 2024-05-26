<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

final readonly class PublishMissingDemandsService
{
    public function __construct(
        private ProjectAllocationsRepository $projectAllocationsRepository,
        private CreateHourlyDemandsSummaryService $createHourlyDemandsSummaryService,
        private EventsPublisher $eventsPublisher,
        private ClockInterface $clock
    ) {
    }

    #[AsCronTask('@hourly')]
    public function publish(): void
    {
        $when = $this->clock->now();
        $projectAllocations = $this->projectAllocationsRepository->findAllContainingDate($when);
        $missingDemands = $this->createHourlyDemandsSummaryService->create($projectAllocations, $when);
        // add metadata to event
        // if needed call EventStore and translate multiple private events to a new published event
        $this->eventsPublisher->publish($missingDemands);
    }
}
