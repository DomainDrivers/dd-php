<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Phpunit;

use PHPUnit\Framework\Constraint\Exception;

trait AssertThrows
{
    final public static function assertThrows(string $className, callable $callable): void
    {
        $e = null;
        try {
            $callable();
        } catch (\Throwable $e) {
        } finally {
            static::assertThat($e, new Exception($className));
        }
    }
}
