<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Phpunit;

use Munus\Value\Comparator;

trait AssertContainsExactlyInAnyOrder
{
    /**
     * @param \Traversable<mixed> $collection
     */
    final public static function assertContainsExactlyInAnyOrder(\Traversable $collection, mixed ...$elements): void
    {
        $remaining = array_values($elements);
        foreach ($collection as $item) {
            $keyToRemove = null;
            foreach ($remaining as $key => $r) {
                if (Comparator::equals($item, $r)) {
                    $keyToRemove = $key;
                    break;
                }
            }
            static::assertNotNull($keyToRemove, 'Redundant item found');
            unset($remaining[$keyToRemove]);
        }

        self::assertEmpty($remaining, 'Not all items were found');
    }
}
