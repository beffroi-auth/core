<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use DateTimeImmutable;
use function hash_equals;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Credential\TotpSecret;
use Beffroi\Core\Port\TotpAlgorithmInterface;
use function intdiv;
use function preg_match;
use function preg_replace;
use function range;
use SensitiveParameter;

/**
 * RFC 6238 §5.2
 */
final readonly class TotpCodeMatcher
{
    public function __construct(
        private TotpAlgorithmInterface $algorithm,
    ) {
    }

    public static function looksLikeCode(#[SensitiveParameter] string $typed): bool
    {
        return preg_match('/^\d{' . TotpCredential::DIGITS . '}$/', self::normalize($typed)) === 1;
    }

    /**
     * Every step of the window is computed and compared in constant time, whatever the first one that matches.
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
