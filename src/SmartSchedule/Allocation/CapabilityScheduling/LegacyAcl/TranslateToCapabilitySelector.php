<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\LegacyAcl;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use Munus\Collection\GenericList;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;

final readonly class TranslateToCapabilitySelector
{
    /**
     * @return GenericList<CapabilitySelector>
     */
    public function translate(EmployeeDataFromLegacyEsbMessage $message): GenericList
    {
        $employeeSkills = $message->skillsPerformedTogether->map(fn (GenericList $skills) => CapabilitySelector::canPerformAllAtTheTime(
            $skills->toStream()->map(fn (string $skill) => Capability::skill($skill))->collect(Collectors::toSet())
        ));

        $employeeExclusiveSkills = $message->exclusiveSkills->map(fn (string $skill) => CapabilitySelector::canJustPerform(Capability::skill($skill)));

        /** @var GenericList<CapabilitySelector> $employeePermissions */
        $employeePermissions = $message->permissions
            ->map(fn (string $permission) => $this->multiplePermission($permission))
            ->flatMap(fn (GenericList $list) => $list);

        // schedule or rewrite if exists;
        return $employeeSkills->appendAll($employeeExclusiveSkills)->appendAll($employeePermissions);
    }

    /**
     * @return GenericList<CapabilitySelector>
     */
    private function multiplePermission(string $permissionLegacyCode): GenericList
    {
        $parts = explode('<>', $permissionLegacyCode);
        $permission = $parts[0];
        $times = (int) $parts[1];
        if ($times <= 0) {
            return GenericList::empty();
        }

        return Stream::range(1, (int) $parts[1])->map(fn () => CapabilitySelector::canJustPerform(Capability::permission($permission)))->collect(Collectors::toList());
    }
}
