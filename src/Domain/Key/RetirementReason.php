<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

enum RetirementReason: string
{
    case Due = 'due';
    case Forced = 'forced';
    case Compromised = 'compromised';
}
