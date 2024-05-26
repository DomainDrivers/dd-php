<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

enum RiskPeriodicCheckSagaStep
{
    case FIND_AVAILABLE;
    case DO_NOTHING;
    case SUGGEST_REPLACEMENT;
    case NOTIFY_ABOUT_POSSIBLE_RISK;
    case NOTIFY_ABOUT_DEMANDS_SATISFIED;
}
