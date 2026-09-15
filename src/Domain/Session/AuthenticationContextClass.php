<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Stringable;

/**
 * An Authentication Context Class Reference, the value of the "acr" claim (OIDC Core §2): what the
 * authentication that established a session satisfied. It is computed from the proofs of the session, never
 * stored nor configured, so that a proof added later moves it the way it moves auth_time (architecture
 * §3.2).
 *
 * The vocabulary is a level of authentication as a numeric string, the one Keycloak emits by default ("1"
 * after a password, "0" once the level expired): a relying party written for it reads the same value after
 * an import (decision 0002). The registered names of RFC 6711 each carry an enrolment meaning the provider
 * does not assert, and OIDC Core §2 forbids using a registered name in another sense; "0" says there is no
 * confidence the same person is there, which is not what a verified password establishes. A second factor
 * will be "2", a passkey "phr" or "phrh" (OpenID Connect EAP ACR Values 1.0): cases to add here.
 */
final readonly class AuthenticationContextClass implements Stringable
{
    /**
     * One factor was proven: the password.
     */
    public const string SINGLE_FACTOR = '1';

    public function __construct(
        public string $value,
    ) {
        // OIDC Core §2: an absolute URI or a registered name, either way printable ASCII without space
        if (preg_match('/^[\x21-\x7E]{1,255}$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a well-formed authentication context class reference.', $value));
        }
    }

    public static function singleFactor(): self
    {
        return new self(self::SINGLE_FACTOR);
    }

    /**
     * The class the proofs of a session satisfy. One credential exists today, so one factor is the most
     * a session proves, and a session holds at least one proof.
     */
    public static function of(AuthenticationProofs $proofs): self
    {
        return self::singleFactor();
    }

    /**
     * What the provider can assert, what "acr_values_supported" lists (OIDC Discovery §3).
     *
     * @return non-empty-list<string>
     */
    public static function supported(): array
    {
        return [self::SINGLE_FACTOR];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
