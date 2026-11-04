<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_any;
use function count;

/**
 * Two proofs of the same kind are one factor (NIST SP 800-63B §2): the kinds are counted, not the methods.
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
