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
final class ParallelizationTest extends TestCase
{
    private StageParallelization $stageParallelization;

    protected function setUp(): void
    {
        $this->stageParallelization = new StageParallelization();
    }

    #[Test]
    public function everythingCanBeDoneInParallelWhenThereAreNoDependencies(): void
    {
        // given
        $stage1 = Stage::of('Stage1');
        $stage2 = Stage::of('Stage2');

        // when
        $sortedStages = $this->stageParallelization->of(Set::of($stage1, $stage2));

        // then
        self::assertEquals(1, $sortedStages->all()->length());
        self::assertEquals('Stage1, Stage2', $sortedStages->print());
    }

    #[Test]
    public function testSimpleDependencies(): void
    {
        // given
        $stage1 = Stage::of('Stage1');
        $stage2 = Stage::of('Stage2');
        $stage3 = Stage::of('Stage3');
        $stage4 = Stage::of('Stage4');
        $stage2->dependsOn($stage1);
        $stage3->dependsOn($stage1);
        $stage4->dependsOn($stage2);

        // when
        $sortedStages = $this->stageParallelization->of(Set::of($stage1, $stage2, $stage3, $stage4));

        // then
        self::assertEquals('Stage1 | Stage2, Stage3 | Stage4', $sortedStages->print());
    }

    #[Test]
    public function cantBeDoneWhenThereIsACycle(): void
    {
        // given
        $stage1 = Stage::of('Stage1');
        $stage2 = Stage::of('Stage2');
        $stage2->dependsOn($stage1);
        $stage1->dependsOn($stage2); // making it cyclic

        // when
        $sortedStages = $this->stageParallelization->of(Set::of($stage1, $stage2));

        // then
        self::assertTrue($sortedStages->all()->isEmpty());
    }
}
