<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * The FIDO certification of a model of authenticator, as the status reports of the FIDO metadata say it: the
 * statuses FIDO Metadata Service v3.1.1 §3.1.4.1 relates to certification, not certified, a self-assessment
 * submitted, certified, certified at one of the six levels, retired by its vendor, and revoked by the FIDO
 * Alliance. The FIPS 140 statuses of the same section are another scheme, the security notifications
 * (§3.1.4.2) and an update available (§3.1.4.3) say nothing of a certification. An enum because the set is
 * the one the specification fixes and nothing acts on a case: a page says it.
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
     * The certification the reports leave the model with: the last of them that states one, the reports
     * coming in the order the service issued them, which is the order webauthn-lib reads the last one in. A
     * status none of these is skipped, one the specification does not define included, which §3.1.4 asks a
     * FIDO server to ignore.
     *
     * @param list<string> $statuses
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
