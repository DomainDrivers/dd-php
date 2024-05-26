<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Cashflow\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cashflow;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashflowRepository;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use Munus\Collection\GenericList;
use Munus\Control\Option;

final readonly class OrmCashflowRepository implements CashflowRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function save(Cashflow $cashflow): void
    {
        $this->entityManager->persist($cashflow);
        $this->entityManager->flush();
    }

    #[\Override]
    public function getById(ProjectAllocationsId $id): Cashflow
    {
        return $this->entityManager->find(Cashflow::class, $id) ?? throw new \RuntimeException(sprintf('Cashflow %s not found', $id->toString()));
    }

    #[\Override]
    public function findById(ProjectAllocationsId $id): Option
    {
        return Option::of($this->entityManager->find(Cashflow::class, $id));
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(Cashflow::class)->findAll());
    }
}
