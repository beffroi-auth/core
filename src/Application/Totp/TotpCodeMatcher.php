<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Totp;

use DateTimeImmutable;
use function hash_equals;
use Heidi\Core\Domain\Credential\TotpCredential;
use Heidi\Core\Domain\Credential\TotpSecret;
use Heidi\Core\Port\TotpAlgorithmInterface;
use function intdiv;
use function preg_match;
use function preg_replace;
use function range;
use SensitiveParameter;

/**
 * Which time step, if any, a typed code is the code of: the current one and one on each side (RFC 6238
 * §5.2, the drift TotpCredential allows and no more). The one place that reads a code and walks the
 * window, for the enrolment and for the authentication alike.
 */
final readonly class TotpCodeMatcher
{
    public function __construct(
        private TotpAlgorithmInterface $algorithm,
    ) {
    }

    /**
     * Whether what was typed has the shape of a code the authenticator computes, once the spaces a user
     * puts between the digits are dropped; anything else is a backup code or nothing.
     */
    public static function looksLikeCode(#[SensitiveParameter] string $typed): bool
    {
        return preg_match('/^\d{' . TotpCredential::DIGITS . '}$/', self::normalize($typed)) === 1;
    }

    /**
     * The step of the window whose code was typed, or null; every step of the window is computed and
     * compared in constant time, whatever the first one that matches. What is not six digits matches
     * nothing, the caller having asked looksLikeCode() first when it has a cheaper path.
     */
    public function matchingStep(TotpSecret $secret, #[SensitiveParameter] string $typed, DateTimeImmutable $at): ?int
    {
        $typed = self::normalize($typed);
        $current = intdiv($at->getTimestamp(), TotpCredential::PERIOD);
        $matched = null;
        foreach (range($current - TotpCredential::DRIFT, $current + TotpCredential::DRIFT) as $step) {
            if ($step >= 0 && hash_equals($this->algorithm->codeAt($secret, $step), $typed) && $matched === null) {
                $matched = $step;
            }
        }

        return $matched;
    }

    private static function normalize(#[SensitiveParameter] string $typed): string
    {
        return (string) preg_replace('/\s+/', '', $typed);
    }
}
