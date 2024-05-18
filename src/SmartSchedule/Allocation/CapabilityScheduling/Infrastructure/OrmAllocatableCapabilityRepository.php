<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapability;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityRepository;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableResourceId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\CapabilityNormalizer;
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
    public function existsById(AllocatableCapabilityId $allocatableCapabilityId): bool
    {
        return $this->entityManager->getRepository(AllocatableCapability::class)->count(['id' => $allocatableCapabilityId]) === 1;
    }

    #[\Override]
    public function findByCapabilityWithin(Capability $capability, TimeSlot $timeSlot): GenericList
    {
        /** @var AllocatableCapability[] $all */
        $all = $this->entityManager->getRepository(AllocatableCapability::class)
            ->createQueryBuilder('ac')
            ->where('JSONB_CONTAINS(ac.possibleCapabilities, :capability) = true')
            ->andWhere('ac.timeSlot.from <= :from')
            ->andWhere('ac.timeSlot.to >= :to')
            ->setParameter('capability', $this->prepareCapabilityParam($capability))
            ->setParameter('from', $timeSlot->from)
            ->setParameter('to', $timeSlot->to)
            ->getQuery()
            ->getResult();

        return GenericList::ofAll($all);
    }

    #[\Override]
    public function findByResourceIdAndCapabilityAndTimeSlot(AllocatableResourceId $allocatableResourceId, Capability $capability, TimeSlot $timeSlot): GenericList
    {
        /** @var AllocatableCapability[] $all */
        $all = $this->entityManager->getRepository(AllocatableCapability::class)
            ->createQueryBuilder('ac')
            ->where('ac.resourceId = :resourceId')
            ->andWhere('JSONB_CONTAINS(ac.possibleCapabilities, :capability) = true')
            ->andWhere('ac.timeSlot.from <= :from')
            ->andWhere('ac.timeSlot.to >= :to')
            ->setParameter('resourceId', $allocatableResourceId, 'allocatable_resource_id')
            ->setParameter('capability', $this->prepareCapabilityParam($capability))
            ->setParameter('from', $timeSlot->from)
            ->setParameter('to', $timeSlot->to)
            ->getQuery()
            ->getResult();

        return GenericList::ofAll($all);
    }

    #[\Override]
    public function findByResourceIdAndTimeSlot(AllocatableResourceId $allocatableResourceId, TimeSlot $timeSlot): GenericList
    {
        /** @var AllocatableCapability[] $all */
        $all = $this->entityManager->getRepository(AllocatableCapability::class)
            ->createQueryBuilder('ac')
            ->where('ac.resourceId = :resourceId')
            ->andWhere('ac.timeSlot.from <= :from')
            ->andWhere('ac.timeSlot.to >= :to')
            ->setParameter('resourceId', $allocatableResourceId, 'allocatable_resource_id')
            ->setParameter('from', $timeSlot->from)
            ->setParameter('to', $timeSlot->to)
            ->getQuery()
            ->getResult();

        return GenericList::ofAll($all);
    }

    private function prepareCapabilityParam(Capability $capability): string
    {
        return (string) \json_encode(['capabilities' => [CapabilityNormalizer::normalize($capability)]]);
    }
}
