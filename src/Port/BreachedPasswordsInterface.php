<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\BreachCount;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Exception\BreachLookupFailedException;
use SensitiveParameter;

/**
 * A list of values known to be commonly used, expected or compromised, which NIST SP 800-63B §3.1.1.2
 * asks a prospective secret to be compared against.
 *
 * **It is a port because a lookup is input and output, and a value object does none.** What a password has
 * to be is a rule of PlainPassword, checked wherever one is built; whether somebody else already holds it
 * is a question for a list this provider does not own, so it is asked through here and the implementation
 * is somebody's bridge.
 *
 * **Only a digest crosses it.** An implementation is handed the SHA-1 of the password and never the
 * password: what it may send out is the prefix of that digest (PasswordDigest), and the comparison is
 * local.
 */
interface BreachedPasswordsInterface
{
    /**
     * Whether this deployment compares a password against a list at all.
     *
     * False for a deployment that names none: it poses passwords and signs people in without asking
     * anybody, which is said where it is configured. It is asked before a digest is computed and before a
     * lookup is ordered, so that a deployment which checks nothing writes nothing to a queue either.
     */
    public function looksUp(): bool;

    /**
     * How many times the list counts this password, zero when it does not hold it, and nothing asked when
     * this deployment names no list.
     *
     * @throws BreachLookupFailedException when the list could not be reached in time, answered a status
     *                                     that is not a success, or answered a body that does not read as
     *                                     counted suffixes
     */
    public function timesSeen(#[SensitiveParameter] PasswordDigest $digest): BreachCount;
}
