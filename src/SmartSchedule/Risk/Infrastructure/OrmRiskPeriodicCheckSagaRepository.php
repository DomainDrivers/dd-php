<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSaga;
use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSagaRepository;
use Munus\Collection\GenericList;

final readonly class OrmRiskPeriodicCheckSagaRepository implements RiskPeriodicCheckSagaRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function save(RiskPeriodicCheckSaga $periodicCheckSaga): void
    {
        $this->entityManager->persist($periodicCheckSaga);
        $this->entityManager->flush();
    }

    #[\Override]
    public function findByProjectId(ProjectAllocationsId $projectId): ?RiskPeriodicCheckSaga
    {
        return $this->entityManager->getRepository(RiskPeriodicCheckSaga::class)->findOneBy(['projectId' => $projectId]);
    }

    #[\Override]
    public function findByProjectIdIn(GenericList $interested): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(RiskPeriodicCheckSaga::class)->findBy([
            'projectId' => $interested->toArray(),
        ]));
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(RiskPeriodicCheckSaga::class)->findAll());
    }
}
