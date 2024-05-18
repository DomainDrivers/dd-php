<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\CapabilityScheduling;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CapabilitySelector::class)]
final class CapabilitySelectorTest extends TestCase
{
    private Capability $rust;
    private Capability $beingAnAdmin;
    private Capability $php;

    protected function setUp(): void
    {
        $this->rust = Capability::skill('rust');
        $this->beingAnAdmin = Capability::permission('admin');
        $this->php = Capability::skill('php');
    }

    #[Test]
    public function allocatableResourceCanPerformOnlyOneOfPresentCapabilities(): void
    {
        // given
        $adminOrRust = CapabilitySelector::canPerformOneOf(Set::of($this->beingAnAdmin, $this->rust));

        // expect
        self::assertTrue($adminOrRust->canPerform($this->beingAnAdmin));
        self::assertTrue($adminOrRust->canPerform($this->rust));
        self::assertFalse($adminOrRust->canPerformAll(Set::of($this->beingAnAdmin, $this->rust)));
        self::assertFalse($adminOrRust->canPerform(Capability::skill('php')));
        self::assertFalse($adminOrRust->canPerform(Capability::permission('lawyer')));
    }

    #[Test]
    public function allocatableResourceCanPerformSimultaneousCapabilities(): void
    {
        // given
        $adminOrRust = CapabilitySelector::canPerformAllAtTheTime(Set::of($this->beingAnAdmin, $this->rust));

        // expect
        self::assertTrue($adminOrRust->canPerform($this->beingAnAdmin));
        self::assertTrue($adminOrRust->canPerform($this->rust));
        self::assertTrue($adminOrRust->canPerformAll(Set::of($this->beingAnAdmin, $this->rust)));
        self::assertFalse($adminOrRust->canPerformAll(Set::of($this->beingAnAdmin, $this->rust, $this->php)));
        self::assertFalse($adminOrRust->canPerform(Capability::skill('php')));
        self::assertFalse($adminOrRust->canPerform(Capability::permission('lawyer')));
    }
}
