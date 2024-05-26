<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocations;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsRepository;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Control\Option;

final readonly class OrmProjectAllocationsRepository implements ProjectAllocationsRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function save(ProjectAllocations $projectAllocations): void
    {
        $this->entityManager->persist($projectAllocations);
        $this->entityManager->flush();
    }

    #[\Override]
    public function getById(ProjectAllocationsId $id): ProjectAllocations
    {
        return $this->entityManager->find(ProjectAllocations::class, $id) ?? throw new \RuntimeException(sprintf('Project allocations %s not found', $id->toString()));
    }

    #[\Override]
    public function findById(ProjectAllocationsId $id): Option
    {
        return Option::of($this->entityManager->find(ProjectAllocations::class, $id));
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(ProjectAllocations::class)->findAll());
    }

    #[\Override]
    public function findAllById(Set $ids): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(ProjectAllocations::class)->findBy([
            'projectId' => $ids->toArray(),
        ]));
    }

    #[\Override]
    public function findAllContainingDate(\DateTimeImmutable $when): GenericList
    {
        /** @var ProjectAllocations[] $all */
        $all = $this->entityManager->getRepository(ProjectAllocations::class)
            ->createQueryBuilder('pa')
            ->andWhere('pa.timeSlot.from <= :when')
            ->andWhere('pa.timeSlot.to > :when')
            ->setParameter('when', $when)
            ->getQuery()
            ->getResult();

        return GenericList::ofAll($all);
    }
}
