<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared;

/**
 * metadata:
 * - correlationId
 * - potential aggregate's id
 * - causationId - id of a message that caused this message
 * - messageId - unique id of the
 * - user - if there is any (might be a system event)
 */
interface PublishedEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
