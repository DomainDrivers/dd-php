<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\CompositePolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\DefaultPolicy;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\OneOfSkills;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeAllocationPolicy\PermissionsInMultipleProjects;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeId;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeSummary;
use DomainDrivers\SmartSchedule\Resource\Employee\Seniority;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\Tests\Phpunit\AssertContainsExactlyInAnyOrder;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompositePolicy::class)]
#[CoversClass(DefaultPolicy::class)]
#[CoversClass(OneOfSkills::class)]
#[CoversClass(PermissionsInMultipleProjects::class)]
final class AllocationPoliciesTest extends TestCase
{
    use AssertContainsExactlyInAnyOrder;

    #[Test]
    public function defaultPolicyShouldReturnJustOneSkillAtOnce(): void
    {
        // given
        $employee = new EmployeeSummary(EmployeeId::newOne(), 'name', 'lastName', Seniority::LEAD, Capability::skills('php'), Capability::permissions('admin'));

        // when
        $capabilities = (new DefaultPolicy())->simultaneousCapabilitiesOf($employee);

        // then
        self::assertSame(1, $capabilities->length());
        self::assertContainsExactlyInAnyOrder($capabilities->get()->capabilities,
            Capability::skill('php'),
            Capability::permission('admin')
        );
    }

    #[Test]
    public function permissionsCanBeSharedBetweenProjects(): void
    {
        // given
        $employee = new EmployeeSummary(EmployeeId::newOne(), 'name', 'lastName', Seniority::LEAD, Capability::skills('php'), Capability::permissions('admin'));

        // when
        $capabilities = (new PermissionsInMultipleProjects(3))->simultaneousCapabilitiesOf($employee);

        // then
        self::assertSame(3, $capabilities->length());
        self::assertContainsExactlyInAnyOrder($capabilities->flatMap(fn (CapabilitySelector $cs) => $cs->capabilities),
            Capability::permission('admin'),
            Capability::permission('admin'),
            Capability::permission('admin')
        );
    }

    #[Test]
    public function canCreateCompositePolicy(): void
    {
        // given
        $policy = new CompositePolicy(GenericList::of(new PermissionsInMultipleProjects(3), new OneOfSkills()));
        $employee = new EmployeeSummary(EmployeeId::newOne(), 'name', 'lastName', Seniority::LEAD, Capability::skills('php', 'k8s'), Capability::permissions('admin'));

        // when
        $capabilities = $policy->simultaneousCapabilitiesOf($employee);

        // then
        self::assertSame(4, $capabilities->length());
        self::assertContainsExactlyInAnyOrder($capabilities,
            CapabilitySelector::canPerformOneOf(Capability::skills('php', 'k8s')),
            CapabilitySelector::canJustPerform(Capability::permission('admin')),
            CapabilitySelector::canJustPerform(Capability::permission('admin')),
            CapabilitySelector::canJustPerform(Capability::permission('admin'))
        );
    }
}
