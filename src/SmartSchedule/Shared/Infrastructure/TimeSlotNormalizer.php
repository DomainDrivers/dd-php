<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;

final class TimeSlotNormalizer
{
    private const string FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @return array{from: string, to: string}
     */
    public static function normalize(TimeSlot $timeSlot): array
    {
        return [
            'from' => $timeSlot->from->format(self::FORMAT),
            'to' => $timeSlot->to->format(self::FORMAT),
        ];
    }

    /**
     * @param array{from: string, to: string} $timeSlot
     */
    public static function denormalize(array $timeSlot): TimeSlot
    {
        $from = \DateTimeImmutable::createFromFormat(self::FORMAT, $timeSlot['from']);
        $to = \DateTimeImmutable::createFromFormat(self::FORMAT, $timeSlot['to']);
        \assert($from instanceof \DateTimeImmutable && $to instanceof \DateTimeImmutable);

        return new TimeSlot($from, $to);
    }
}
