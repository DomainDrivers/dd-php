<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\Seniority;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(EmployeeFacade::class)]
final class CreatingEmployeeTest extends KernelTestCase
{
    private EmployeeFacade $employeeFacade;

    protected function setUp(): void
    {
        $this->employeeFacade = self::getContainer()->get(EmployeeFacade::class);
    }

    #[Test]
    public function canCreateAndLoadEmployee(): void
    {
        // given
        $employee = $this->employeeFacade->addEmployee('resourceName', 'lastName', Seniority::SENIOR, Capability::skills('PHP', 'PYTHON'), Capability::permissions('ADMIN', 'COURT'));

        // when
        $loaded = $this->employeeFacade->findEmployee($employee);

        // then
        self::assertTrue($loaded->skills->equals(Capability::skills('PHP', 'PYTHON')));
        self::assertTrue($loaded->permissions->equals(Capability::permissions('ADMIN', 'COURT')));
        self::assertSame('resourceName', $loaded->name);
        self::assertSame('lastName', $loaded->lastName);
        self::assertSame(Seniority::SENIOR, $loaded->seniority);
    }

    #[Test]
    public function canFindAllCapabilities(): void
    {
        // given
        $this->employeeFacade->addEmployee('staszek', 'lastName', Seniority::SENIOR, Capability::skills('PHP12', 'PYTHON21'), Capability::permissions('ADMIN1', 'COURT1'));
        $this->employeeFacade->addEmployee('leon', 'lastName', Seniority::SENIOR, Capability::skills('PHP12', 'PYTHON21'), Capability::permissions('ADMIN2', 'COURT2'));
        $this->employeeFacade->addEmployee('sławek', 'lastName', Seniority::SENIOR, Capability::skills('PHP12', 'PYTHON21'), Capability::permissions('ADMIN3', 'COURT3'));

        // when
        $loaded = $this->employeeFacade->findAllCapabilities();

        // then
        self::assertTrue($loaded->containsAll(GenericList::of(
            Capability::permission('ADMIN1'),
            Capability::permission('ADMIN2'),
            Capability::permission('ADMIN3'),
            Capability::permission('COURT1'),
            Capability::permission('COURT2'),
            Capability::permission('COURT3'),
            Capability::skill('PHP12'),
            Capability::skill('PHP12'),
            Capability::skill('PHP12'),
            Capability::skill('PYTHON21'),
            Capability::skill('PYTHON21'),
            Capability::skill('PYTHON21'),
        )));
    }
}
