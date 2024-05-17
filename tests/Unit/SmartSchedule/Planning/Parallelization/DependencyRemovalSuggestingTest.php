<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Parallelization\StageParallelization;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(StageParallelization::class)]
final class DependencyRemovalSuggestingTest extends TestCase
{
    #[Test]
    public function suggestingBreaksTheCycleInSchedule(): void
    {
        // given
        $stage1 = Stage::of('Stage1');
        $stage2 = Stage::of('Stage2');
        $stage3 = Stage::of('Stage3');
        $stage4 = Stage::of('Stage4');
        $stage2 = $stage2->dependsOn($stage3);
        $stage4 = $stage4->dependsOn($stage3);
        $stage3 = $stage3->dependsOn($stage1);
        $stage1 = $stage1->dependsOn($stage4);
        $stage1 = $stage1->dependsOn($stage2);

        // when
        $suggestion = (new StageParallelization())->whatToRemove(Set::of($stage1, $stage2, $stage3, $stage4));

        // then
        self::assertSame('(3 -> 1), (4 -> 3)', (string) $suggestion);
    }
}
