<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * FIDO Metadata Service v3.1.1 §3.1.4.1
 */
enum FidoCertification: string
{
    case NotCertified = 'NOT_FIDO_CERTIFIED';
    case SelfAssertionSubmitted = 'SELF_ASSERTION_SUBMITTED';
    case Certified = 'FIDO_CERTIFIED';
    case Level1 = 'FIDO_CERTIFIED_L1';
    case Level1Plus = 'FIDO_CERTIFIED_L1plus';
    case Level2 = 'FIDO_CERTIFIED_L2';
    case Level2Plus = 'FIDO_CERTIFIED_L2plus';
    case Level3 = 'FIDO_CERTIFIED_L3';
    case Level3Plus = 'FIDO_CERTIFIED_L3plus';
    case Retired = 'RETIRED';
    case Revoked = 'REVOKED';

    /**
     * FIDO Metadata Service v3.1.1 §3.1.4
     *
     * @param list<string> $statuses in the order the service issued them
     */
    public static function lastOf(array $statuses): ?self
    {
        $certification = null;
        foreach ($statuses as $status) {
            $certification = self::tryFrom($status) ?? $certification;
        }

        return $certification;
    }
}
