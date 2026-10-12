<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_any;
use function count;

/**
 * Two methods of different kinds (knowledge, possession, inherence, RFC 8176 §2), or a method that counts
 * as several factors by itself ("mfa"). Two proofs of the same kind are one factor (NIST SP 800-63B §2),
 * which is why this reads the kinds of a set of methods and not their number.
 *
 * It is the one predicate of decision 0010 that reads the proofs of a login together rather than one by
 * one: a password alone satisfies nothing here, and is still half of what satisfies it, which is what
 * makes it a credential this predicate admits as the floor of a tenant.
 */
final readonly class TwoFactors extends AuthenticationPredicate
{
    public const string NAME = 'two_factors';

    public function name(): string
    {
        return self::NAME;
    }

    public function isSatisfiedBy(array $methods): bool
    {
        return (
            count(AuthenticationMethod::factorsOf($methods)) >= 2
            || array_any($methods, static fn (AuthenticationMethod $method): bool => $method->isMultipleFactors())
        );
    }

    protected function reads(AuthenticationMethod $method): bool
    {
        return $method->factor() !== null || $method->isMultipleFactors();
    }
}
