<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapability;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityRepository;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;

final readonly class OrmAllocatableCapabilityRepository implements AllocatableCapabilityRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function saveAll(GenericList $all): void
    {
        foreach ($all->toArray() as $allocatableCapability) {
            $this->entityManager->persist($allocatableCapability);
        }
        $this->entityManager->flush();
    }

    #[\Override]
    public function findAllById(GenericList $ids): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(AllocatableCapability::class)->findBy([
            'id' => $ids->toArray(),
        ]));
    }

    #[\Override]
    public function findByCapabilityWithin(Capability $capability, TimeSlot $timeSlot): GenericList
    {
        /** @var AllocatableCapability[] $all */
        $all = $this->entityManager->getRepository(AllocatableCapability::class)
            ->createQueryBuilder('ac')
            ->where('ac.capability = :capability')
            ->andWhere('ac.timeSlot.from <= :from')
            ->andWhere('ac.timeSlot.to >= :to')
            ->setParameter('capability', $capability, 'capability')
            ->setParameter('from', $timeSlot->from)
            ->setParameter('to', $timeSlot->to)
            ->getQuery()
            ->getResult();

        return GenericList::ofAll($all);
    }
}
