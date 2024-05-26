<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Shared\TimeSlot;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Duration::class)]
final class DurationTest extends TestCase
{
    #[Test]
    public function canBeAdded(): void
    {
        self::assertTrue(Duration::ofHours(3)->equals(Duration::ofHours(2)->plus(Duration::ofHours(1))));
        self::assertTrue(Duration::ofDays(1)->equals(Duration::ofHours(23)->plus(Duration::ofHours(1))));
        self::assertTrue(Duration::ofMinutes(7)->equals(Duration::ofMinutes(3)->plus(Duration::ofMinutes(4))));
    }

    #[Test]
    public function canBeConvertedToMinutes(): void
    {
        self::assertSame(60, Duration::ofHours(1)->toMinutes());
        self::assertSame(1440, Duration::ofDays(1)->toMinutes());
        self::assertSame(0, (new Duration(59))->toMinutes());
        self::assertSame(1, (new Duration(60))->toMinutes());
        self::assertSame(1, (new Duration(61))->toMinutes());
    }

    #[Test]
    public function throwsExceptionIfEndBeforeStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Duration::between(new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2023-01-01'));
    }

    #[Test]
    public function canBeCreatedFromInterval(): void
    {
        self::assertSame(0, Duration::between(new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2024-01-01'))->seconds);
        self::assertSame(15, Duration::between(new \DateTimeImmutable('2024-01-01 00:00:00'), new \DateTimeImmutable('2024-01-01 00:00:15'))->seconds);
        self::assertSame(86400, Duration::between(new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2024-01-02'))->seconds);
    }
}
