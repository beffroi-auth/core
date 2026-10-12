<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use function mb_strlen;
use SensitiveParameter;

/**
 * How long a password has to be, which NIST SP 800-63B §3.1.1.2 makes two numbers and not one: fifteen
 * characters for a password "used as a single-factor authentication mechanism", eight for one "only used
 * as part of multi-factor authentication processes". Both are a SHALL, so the number is read from what the
 * login the password serves has to prove, and a constant could only be the wrong one of the two.
 *
 * **The floor of the logins of a tenant answers it** (decision 0025), and by the question the predicate
 * already holds: a password signs somebody in alone exactly when the floor is satisfied by a login that
 * proved what a password proves (AuthenticationPredicateInterface::isSatisfiedBy()). Under
 * `single_factor` a password is the whole of a login, so fifteen; under `two_factors` it is half of one,
 * so eight. No predicate is named here, so a predicate this provider gains is read by the same question
 * and there is nothing to edit.
 *
 * A floor that reads nothing a password proves, `phishing_resistant`, holds no password at all: what is
 * answered here of such a floor never reaches a password, the enrolment being refused first
 * (EnrolmentRefusal) and the login with it (PasswordVerification).
 *
 * Counted in characters, which is what the same section asks ("each Unicode code point SHALL be counted
 * as a single character when evaluating password length"). The bound counted in bytes is the upper one,
 * which is PlainPassword's and belongs to the hasher rather than to the login.
 */
final readonly class PasswordLength
{
    /**
     * A password that is the whole of a login.
     */
    public const int ALONE = 15;

    /**
     * A password a second factor is read beside, which the same section lets a verifier allow to be
     * shorter, down to this and no further.
     */
    public const int WITH_A_SECOND_FACTOR = 8;

    private function __construct(
        public int $minimum,
    ) {
    }

    public static function under(AuthenticationPredicateInterface $floor): self
    {
        return new self(
            $floor->isSatisfiedBy([PasswordCredential::method()]) ? self::ALONE : self::WITH_A_SECOND_FACTOR,
        );
    }

    public function reachedBy(#[SensitiveParameter] string $value): bool
    {
        return mb_strlen($value) >= $this->minimum;
    }
}
