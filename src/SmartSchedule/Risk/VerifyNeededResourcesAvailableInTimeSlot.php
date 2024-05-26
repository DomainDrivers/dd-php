<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\NeededResourcesChosen;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class VerifyNeededResourcesAvailableInTimeSlot
{
    public function __construct(
        private AvailabilityFacade $availabilityFacade,
        private RiskPushNotification $riskPushNotification,
    ) {
    }

    #[AsMessageHandler(bus: 'event')]
    public function handle(NeededResourcesChosen $neededResourcesChosen): void
    {
        $this->notifyAboutNotAvailableResources($neededResourcesChosen->neededResources, $neededResourcesChosen->timeSlot, $neededResourcesChosen->projectId);
    }

    /**
     * @param Set<ResourceId> $resourcedIds
     */
    private function notifyAboutNotAvailableResources(Set $resourcedIds, TimeSlot $timeSlot, ProjectId $projectId): void
    {
        /** @var Set<ResourceId> $notAvailable */
        $notAvailable = Set::empty();
        $calendars = $this->availabilityFacade->loadCalendars($resourcedIds, $timeSlot);

        foreach ($resourcedIds->toArray() as $resourceId) {
            if ($calendars->get($resourceId)->availableSlots()->noneMatch(fn (TimeSlot $slot) => $timeSlot->within($slot))) {
                $notAvailable = $notAvailable->add($resourceId);
            }
        }

        if (!$notAvailable->isEmpty()) {
            $this->riskPushNotification->notifyAboutResourcesNotAvailable($projectId, $notAvailable);
        }
    }
}
