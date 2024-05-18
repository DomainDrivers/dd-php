<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\CapabilityScheduling\LegacyAcl;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\LegacyAcl\EmployeeDataFromLegacyEsbMessage;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\LegacyAcl\TranslateToCapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Phpunit\AssertContainsExactlyInAnyOrder;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(TranslateToCapabilitySelector::class)]
final class TranslateToCapabilitySelectorTest extends TestCase
{
    use AssertContainsExactlyInAnyOrder;

    #[Test]
    public function translateLegacyEsbMessageToCapabilitySelectorModel(): void
    {
        // give
        $legacyPermissions = GenericList::of('ADMIN<>2', 'ROOT<>1');
        $legacySkillsPerformedTogether = GenericList::of(
            GenericList::of('PHP', 'CSHARP', 'PYTHON'),
            GenericList::of('RUST', 'CSHARP', 'PYTHON'),
        );
        $legacyExclusiveSkills = GenericList::of('YT DRAMA COMMENTS');

        // when
        $result = $this->translate($legacySkillsPerformedTogether, $legacyExclusiveSkills, $legacyPermissions);

        // then
        self::assertContainsExactlyInAnyOrder($result,
            CapabilitySelector::canPerformOneOf(Capability::skills('YT DRAMA COMMENTS')),
            CapabilitySelector::canPerformAllAtTheTime(Capability::skills('PHP', 'CSHARP', 'PYTHON')),
            CapabilitySelector::canPerformAllAtTheTime(Capability::skills('RUST', 'CSHARP', 'PYTHON')),
            CapabilitySelector::canPerformOneOf(Capability::permissions('ADMIN')),
            CapabilitySelector::canPerformOneOf(Capability::permissions('ADMIN')),
            CapabilitySelector::canPerformOneOf(Capability::permissions('ROOT'))
        );
    }

    #[Test]
    public function zeroMeansNoPermissionNowhere(): void
    {
        // when
        $result = $this->translate(GenericList::empty(), GenericList::empty(), GenericList::of('ADMIN<>0'));

        // then
        self::assertTrue($result->isEmpty());
    }

    /**
     * @param GenericList<GenericList<string>> $legacySkillsPerformedTogether
     * @param GenericList<string>              $legacyExclusiveSkills
     * @param GenericList<string>              $legacyPermissions
     *
     * @return GenericList<CapabilitySelector>
     */
    private function translate(GenericList $legacySkillsPerformedTogether, GenericList $legacyExclusiveSkills, GenericList $legacyPermissions): GenericList
    {
        return (new TranslateToCapabilitySelector())->translate(new EmployeeDataFromLegacyEsbMessage(
            Uuid::v7(),
            $legacySkillsPerformedTogether,
            $legacyExclusiveSkills,
            $legacyPermissions,
            TimeSlot::empty()
        ));
    }
}
