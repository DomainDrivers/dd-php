<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use DomainDrivers\SmartSchedule\Availability\Blockade;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityId;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityRepository;
use DomainDrivers\SmartSchedule\Availability\ResourceGroupedAvailability;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\BulkInsertQuery;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

final readonly class DbalResourceAvailabilityRepository implements ResourceAvailabilityRepository
{
    public function __construct(private Connection $connection)
    {
    }

    #[\Override]
    public function saveNew(ResourceAvailability $resourceAvailability): void
    {
        $this->connection->executeQuery('INSERT INTO availabilities 
        (id, resource_id, resource_parent_id, from_date, to_date, taken_by, disabled, version) 
        VALUES 
        (:id, :resourceId, :resourceParentId, :fromDate, :toDate, :takenBy, :disabled, :version)', [
            'id' => $resourceAvailability->id->toString(),
            'resourceId' => $resourceAvailability->resourceId->toString(),
            'resourceParentId' => $resourceAvailability->resourceParentId->toString(),
            'fromDate' => $resourceAvailability->segment->from,
            'toDate' => $resourceAvailability->segment->to,
            'takenBy' => null,
            'disabled' => false,
            'version' => 0,
        ], [
            'fromDate' => Types::DATETIME_IMMUTABLE,
            'toDate' => Types::DATETIME_IMMUTABLE,
            'disabled' => Types::BOOLEAN,
        ]);
    }

    #[\Override]
    public function saveGroup(ResourceGroupedAvailability $groupedAvailability): void
    {
        $bulkInsert = (new BulkInsertQuery($this->connection, 'availabilities'))
            ->setColumns(['id', 'resource_id', 'resource_parent_id', 'from_date', 'to_date', 'taken_by', 'disabled', 'version']);

        foreach (array_chunk($groupedAvailability->resourceAvailabilities->toArray(), 500) as $chunk) {
            $bulkInsert
                ->setValues(array_map(fn (ResourceAvailability $resourceAvailability) => [
                    $resourceAvailability->id->toString(),
                    $resourceAvailability->resourceId->toString(),
                    $resourceAvailability->resourceParentId->toString(),
                    $resourceAvailability->segment->from,
                    $resourceAvailability->segment->to,
                    null,
                    false,
                    0,
                ], $chunk), [
                    Types::STRING,
                    Types::STRING,
                    Types::STRING,
                    Types::DATETIME_IMMUTABLE,
                    Types::DATETIME_IMMUTABLE,
                    Types::STRING,
                    Types::BOOLEAN,
                    Types::BIGINT,
                ])
                ->execute();
        }
    }

    #[\Override]
    public function loadAllWithinSlot(ResourceId $resourceId, TimeSlot $segment): GenericList
    {
        return GenericList::ofAll(array_map(fn ($row) => $this->map($row), $this->connection->fetchAllAssociative(
            'select * from availabilities where resource_id = :resourceId and from_date >= :fromDate and to_date <= :toDate',
            [
                'resourceId' => $resourceId->toString(),
                'fromDate' => $segment->from,
                'toDate' => $segment->to,
            ], [
                'fromDate' => Types::DATETIME_IMMUTABLE,
                'toDate' => Types::DATETIME_IMMUTABLE,
            ]
        )));
    }

    #[\Override]
    public function loadAllByParentIdWithinSlot(ResourceId $parentId, TimeSlot $segment): GenericList
    {
        return GenericList::ofAll(array_map(fn ($row) => $this->map($row), $this->connection->fetchAllAssociative(
            'select * from availabilities where resource_parent_id = :parentId and from_date >= :fromDate and to_date <= :toDate',
            [
                'parentId' => $parentId->toString(),
                'fromDate' => $segment->from,
                'toDate' => $segment->to,
            ], [
                'fromDate' => Types::DATETIME_IMMUTABLE,
                'toDate' => Types::DATETIME_IMMUTABLE,
            ]
        )));
    }

    #[\Override]
    public function saveCheckingVersion(ResourceAvailability $resourceAvailability): bool
    {
        return (int) $this->connection->executeStatement(
            'UPDATE availabilities SET taken_by = :takenBy, disabled = :disabled, version = :version WHERE id = :id AND version = :updateVersion',
            [
                'takenBy' => $resourceAvailability->blockedBy()->toString(),
                'disabled' => $resourceAvailability->isDisabled(),
                'version' => $resourceAvailability->version() + 1,
                'id' => $resourceAvailability->id->toString(),
                'updateVersion' => $resourceAvailability->version(),
            ], [
                'disabled' => Types::BOOLEAN,
            ]
        ) === 1;
    }

    #[\Override]
    public function saveCheckingVersions(ResourceGroupedAvailability $groupedAvailability): bool
    {
        return $groupedAvailability->resourceAvailabilities
            ->map(fn (ResourceAvailability $r) => $this->saveCheckingVersion($r))
            ->allMatch(fn (bool $result) => $result === true);
    }

    #[\Override]
    public function loadById(ResourceAvailabilityId $availabilityId): ?ResourceAvailability
    {
        $row = $this->connection->fetchAssociative('select * from availabilities where id = :id', ['id' => $availabilityId->toString()]);
        if ($row === false) {
            return null;
        }

        return $this->map($row);
    }

    #[\Override]
    public function loadAvailabilitiesOfRandomResourceWithin(Set $resourcesId, TimeSlot $normalized): ResourceGroupedAvailability
    {
        $randomResource = $this->connection->fetchOne(
            'select * from availabilities where resource_id = ANY(:ids) and taken_by is null and from_date >= :fromDate and to_date <= :toDate order by random() limit 1',
            [
                'ids' => $resourcesId->map(fn (ResourceAvailabilityId $i) => $i->toString())->toArray(),
                'fromDate' => $normalized->from,
                'toDate' => $normalized->to,
            ], [
                'fromDate' => Types::DATETIME_IMMUTABLE,
                'toDate' => Types::DATETIME_IMMUTABLE,
            ]
        );

        if ($randomResource === false) {
            return new ResourceGroupedAvailability(GenericList::empty());
        }

        return new ResourceGroupedAvailability(GenericList::ofAll(array_map(fn ($row) => $this->map($row), $this->connection->fetchAllAssociative(
            'select * from availabilities where resource_id = :randomResource',
            [
                'randomResource' => $randomResource,
            ]
        ))));
    }

    /**
     * @param mixed[] $row
     */
    private function map(array $row): ResourceAvailability
    {
        /** @var array{id: string, resource_id: string, resource_parent_id: ?string, from_date: string, to_date: string, taken_by: ?string, disabled: bool, version: int} $row */
        return new ResourceAvailability(
            ResourceAvailabilityId::fromString($row['id']),
            ResourceId::fromString($row['resource_id']),
            $row['resource_parent_id'] !== null ? ResourceId::fromString($row['resource_parent_id']) : ResourceId::none(),
            new TimeSlot(new \DateTimeImmutable($row['from_date']), new \DateTimeImmutable($row['to_date'])),
            new Blockade($row['taken_by'] !== null ? Owner::fromString($row['taken_by']) : Owner::none(), $row['disabled']),
            $row['version']
        );
    }
}
