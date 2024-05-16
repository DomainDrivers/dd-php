<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability\Infrastructure;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use DomainDrivers\SmartSchedule\Availability\Calendar;
use DomainDrivers\SmartSchedule\Availability\Calendars;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceAvailabilityReadModel;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Set;

final readonly class DbalResourceAvailabilityReadModel implements ResourceAvailabilityReadModel
{
    private const string QUERY = 'WITH AvailabilityWithLag AS (
                SELECT
                    resource_id,
                    taken_by,
                    from_date,
                    to_date,
                    COALESCE(LAG(to_date) OVER (PARTITION BY resource_id, taken_by ORDER BY from_date), from_date) AS prev_to_date
                FROM  
                    availabilities
                WHERE
                    from_date >= :from_date 
                    AND to_date <= :to_date
                    AND resource_id IN (:resources)
                
            ),
            GroupedAvailability AS (
                SELECT
                    resource_id,
                    taken_by,
                    from_date,
                    to_date,
                    prev_to_date,
                    CASE WHEN
                        from_date = prev_to_date
                        THEN 0 ELSE 1 END
                    AS new_group_flag,
                    SUM(CASE WHEN
                        from_date = prev_to_date
                        THEN 0 ELSE 1 END)
                    OVER (PARTITION BY resource_id, taken_by ORDER BY from_date) AS grp
                FROM  
                    AvailabilityWithLag
            )
            SELECT
                resource_id,
                taken_by,
                MIN(from_date) AS start_date,
                MAX(to_date) AS end_date
            FROM
                GroupedAvailability
            GROUP BY
                resource_id, taken_by, grp
            ORDER BY
                start_date;';

    public function __construct(private Connection $connection)
    {
    }

    #[\Override]
    public function load(ResourceId $resourceId, TimeSlot $timeSlot): Calendar
    {
        $loaded = $this->loadAll(Set::of($resourceId), $timeSlot);

        return $loaded->get($resourceId);
    }

    #[\Override]
    public function loadAll(Set $resourceIds, TimeSlot $timeSlot): Calendars
    {
        $calendars = [];
        /** @var array{resource_id: string, taken_by: ?string, start_date: string, end_date: string} $row */
        foreach ($this->connection->fetchAllAssociative(self::QUERY, [
            'from_date' => $timeSlot->from,
            'to_date' => $timeSlot->to,
            'resources' => $resourceIds->map(fn (ResourceId $id) => (string) $id)->toArray(),
        ], [
            'from_date' => Types::DATETIME_IMMUTABLE,
            'to_date' => Types::DATETIME_IMMUTABLE,
            'resources' => ArrayParameterType::STRING,
        ]) as $row) {
            $loadedSlot = new TimeSlot(new \DateTimeImmutable($row['start_date']), new \DateTimeImmutable($row['end_date']));
            $takenBy = $row['taken_by'] !== null ? Owner::fromString($row['taken_by']) : Owner::none();
            $calendars[$row['resource_id']] ??= new Calendar(ResourceId::fromString($row['resource_id']), Map::empty());
            $calendars[$row['resource_id']] = new Calendar(
                ResourceId::fromString($row['resource_id']),
                $calendars[$row['resource_id']]->calendar->put((string) $takenBy, $calendars[$row['resource_id']]->calendar->get((string) $takenBy)->getOrElse(GenericList::empty())->append($loadedSlot))
            );
        }

        return new Calendars(Map::fromArray($calendars));
    }
}
