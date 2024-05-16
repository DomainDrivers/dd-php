<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

enum Seniority: string
{
    case JUNIOR = 'junior';
    case MID = 'mid';
    case SENIOR = 'senior';
    case LEAS = 'lead';
}
