<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(PlanningFacade::class)]
final class PlanningFacadeIntegrationTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private PlanningFacade $projectFacade;

    #[\Override]
    protected function setUp(): void
    {
        self::getContainer()->get('snc_redis.default')->flushAll();
        $this->projectFacade = self::getContainer()->get(PlanningFacade::class);
    }

    #[Test]
    public function canCreateProjectAndLoadProjectCard(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $loaded = $this->projectFacade->load($projectId);

        // then
        self::assertTrue($projectId->id->equals($loaded->projectId->id));
        self::assertSame('project', $loaded->name);
        self::assertSame('Stage1', $loaded->parallelStagesList->print());
    }
}
