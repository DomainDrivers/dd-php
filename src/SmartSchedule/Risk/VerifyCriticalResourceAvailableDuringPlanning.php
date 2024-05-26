<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\Calendar;
use DomainDrivers\SmartSchedule\Planning\CriticalStagePlanned;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class VerifyCriticalResourceAvailableDuringPlanning
{
    public function __construct(
        private AvailabilityFacade $availabilityFacade,
        private RiskPushNotification $riskPushNotification
    ) {
    }

    #[AsMessageHandler(bus: 'event')]
    public function handle(CriticalStagePlanned $criticalStagePlanned): void
    {
        if ($criticalStagePlanned->criticalResource === null) {
            return;
        }

        $calendar = $this->availabilityFacade->loadCalendar($criticalStagePlanned->criticalResource, $criticalStagePlanned->stageTimeSlot);
        if (!$this->resourceIsAvailable($criticalStagePlanned->stageTimeSlot, $calendar)) {
            $this->riskPushNotification->notifyAboutCriticalResourceNotAvailable(
                $criticalStagePlanned->projectId,
                $criticalStagePlanned->criticalResource,
                $criticalStagePlanned->stageTimeSlot
            );
        }
    }

    private function resourceIsAvailable(TimeSlot $timeSlot, Calendar $calendar): bool
    {
        return $calendar->availableSlots()->anyMatch(fn (TimeSlot $slot) => $slot->equals($timeSlot));
    }
}
