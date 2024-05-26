<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning\Infrastructure;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\ChosenResources;
use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\DemandsPerStage;
use DomainDrivers\SmartSchedule\Planning\Infrastructure\RedisProjectRepository;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStages;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Project;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Phpunit\AssertContainsExactlyInAnyOrder;
use Munus\Collection\Map;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(RedisProjectRepository::class)]
final class RedisProjectRepositoryTest extends KernelTestCase
{
    use AssertContainsExactlyInAnyOrder;

    private RedisProjectRepository $redisProjectRepository;
    private TimeSlot $jan10_20;
    private ParallelStagesList $stages;

    protected function setUp(): void
    {
        $this->redisProjectRepository = self::getContainer()->get(RedisProjectRepository::class);
        self::getContainer()->get('snc_redis.default')->flushAll();

        $this->jan10_20 = TimeSlot::with('2020-01-10 00:00:00.00', '2020-01-20 00:00:00.00');
        $this->stages = ParallelStagesList::of(new ParallelStages(Set::of(Stage::of('Stage1'))));
    }

    #[Test]
    public function canSaveAndLoadProject(): void
    {
        // given
        $project = new Project('project', $this->stages);
        $project->addSchedule(new Schedule(Map::fromArray(['Stage1' => $this->jan10_20])));
        $project->addDemands(Demands::of(Demand::forSkill('php')));
        $project->addChosenResources(new ChosenResources(Set::of(ResourceId::newOne()), $this->jan10_20));
        $project->addDemandsPerStage(DemandsPerStage::empty());
        $this->redisProjectRepository->save($project);

        // when
        $loaded = $this->redisProjectRepository->getById($project->id());

        // then
        self::assertEquals($project, $loaded);
    }

    #[Test]
    public function canLoadMultipleProjects(): void
    {
        // given
        $project = new Project('project', $this->stages);
        $project2 = new Project('project2', $this->stages);
        // and
        $this->redisProjectRepository->save($project);
        $this->redisProjectRepository->save($project2);

        // when
        $loaded = $this->redisProjectRepository->findAllById(Set::of($project->id(), $project2->id()));

        // then
        self::assertSame(2, $loaded->length());
        self::assertContainsExactlyInAnyOrder($loaded->map(fn (Project $p) => $p->id()), $project->id(), $project2->id());
    }

    #[Test]
    public function canLoadAllProjects(): void
    {
        // given
        $project = new Project('project', $this->stages);
        $project2 = new Project('project2', $this->stages);
        // and
        $this->redisProjectRepository->save($project);
        $this->redisProjectRepository->save($project2);

        // when
        $loaded = $this->redisProjectRepository->findAll();

        // then
        self::assertSame(2, $loaded->length());
        self::assertContainsExactlyInAnyOrder($loaded->map(fn (Project $p) => $p->id()), $project->id(), $project2->id());
    }
}
