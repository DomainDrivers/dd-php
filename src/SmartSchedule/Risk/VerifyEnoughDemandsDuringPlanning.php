<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Planning\CapabilitiesDemanded;
use DomainDrivers\SmartSchedule\Planning\Demand as PlanningDemand;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectCard;
use DomainDrivers\SmartSchedule\Resource\ResourceFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\AvailableResourceCapability;
use DomainDrivers\SmartSchedule\Simulation\Demand;
use DomainDrivers\SmartSchedule\Simulation\Demands;
use DomainDrivers\SmartSchedule\Simulation\ProjectId;
use DomainDrivers\SmartSchedule\Simulation\SimulatedCapabilities;
use DomainDrivers\SmartSchedule\Simulation\SimulatedProject;
use DomainDrivers\SmartSchedule\Simulation\SimulationFacade;
use Munus\Collection\GenericList;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

final readonly class VerifyEnoughDemandsDuringPlanning
{
    private const int SAME_ARBITRARY_VALUE_FOR_EVERY_PROJECT = 100;

    public function __construct(
        private PlanningFacade $planningFacade,
        private SimulationFacade $simulationFacade,
        private ResourceFacade $resourceFacade,
        private RiskPushNotification $riskPushNotification
    ) {
    }

    #[AsMessageHandler(bus: 'event')]
    public function handle(CapabilitiesDemanded $capabilitiesDemanded): void
    {
        $projectSummaries = $this->planningFacade->findAll();
        $allCapabilities = $this->resourceFacade->findAllCapabilities();
        if ($this->notAbleToHandleAllProjectsGivenCapabilities($projectSummaries, $allCapabilities)) {
            $this->riskPushNotification->notifyAboutPossibleRiskDuringPlanning($capabilitiesDemanded->projectId, $capabilitiesDemanded->demands);
        }
    }

    /**
     * @param GenericList<ProjectCard> $projectSummaries
     * @param GenericList<Capability>  $allCapabilities
     */
    private function notAbleToHandleAllProjectsGivenCapabilities(GenericList $projectSummaries, GenericList $allCapabilities): bool
    {
        $capabilities = $allCapabilities->map(fn (Capability $c) => new AvailableResourceCapability(Uuid::v7(), CapabilitySelector::canJustPerform($c), TimeSlot::empty()));
        $simulatedProjects = $projectSummaries->map($this->createSamePriceSimulatedProject(...));
        $result = $this->simulationFacade->whatIsTheOptimalSetup($simulatedProjects, new SimulatedCapabilities($capabilities));

        return $result->chosenItems->length() !== $projectSummaries->length();
    }

    private function createSamePriceSimulatedProject(ProjectCard $card): SimulatedProject
    {
        return new SimulatedProject(
            ProjectId::fromString($card->projectId->toString()),
            fn () => new Decimal(self::SAME_ARBITRARY_VALUE_FOR_EVERY_PROJECT),
            new Demands($card->demands->all->map(fn (PlanningDemand $d) => Demand::for($d->capability, TimeSlot::empty())))
        );
    }
}
