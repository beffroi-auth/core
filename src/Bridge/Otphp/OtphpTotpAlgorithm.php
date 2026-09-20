<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Otphp;

use Heidi\Core\Domain\Credential\TotpCredential;
use Heidi\Core\Domain\Credential\TotpSecret;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Port\TotpAlgorithmInterface;
use function intdiv;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use const PHP_INT_MAX;
use Psr\Clock\ClockInterface;
use function sprintf;
use function str_replace;

/**
 * RFC 6238 by spomky-labs/otphp, with the parameters of TotpCredential and nothing else: the brick does
 * the HMAC and the truncation of RFC 4226 §5.3, the core reads the steps.
 */
final readonly class OtphpTotpAlgorithm implements TotpAlgorithmInterface
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    /**
     * The brick takes a Unix time and divides it by the period (RFC 6238 §4.2); the first second of the
     * step is given, so that the step is what it is asked for.
     */
    public function codeAt(TotpSecret $secret, int $timeStep): string
    {
        if ($timeStep < 0 || $timeStep > intdiv(PHP_INT_MAX, TotpCredential::PERIOD)) {
            throw new InvalidArgumentException('A time step is never negative (RFC 6238 §4.2), nor beyond what a Unix time counts.');
        }
        /** @var int<0, max> $unixTime the step is bounded on both sides */
        $unixTime = $timeStep * TotpCredential::PERIOD;

        return $this->totp($secret)
            ->at($unixTime);
    }

    /**
     * The label is "issuer:account" (the Key Uri Format), so neither carries a colon: one that would is
     * shown with a space instead.
     */
    public function provisioningUri(TotpSecret $secret, string $issuer, string $account): string
    {
        return $this->totp($secret)
            ->withIssuer(self::labelPart($issuer, 'issuer'))
            ->withLabel(self::labelPart($account, 'account'))
            ->getProvisioningUri();
    }

    private function totp(TotpSecret $secret): TOTP
    {
        /** @var non-empty-string $encoded at least sixteen bytes, so at least twenty-six characters */
        $encoded = Base32::encodeUpperUnpadded($secret->bytes);

        return TOTP::createFromSecret($encoded, $this->clock)
            ->withPeriod(TotpCredential::PERIOD)
            ->withDigits(TotpCredential::DIGITS)
            ->withDigest(TotpCredential::ALGORITHM);
    }

    /**
     * @return non-empty-string
     */
    private static function labelPart(string $value, string $name): string
    {
        $part = str_replace(':', ' ', $value);
        if ($part === '') {
            throw new InvalidArgumentException(sprintf('The %s of a provisioning URI is not empty.', $name));
        }

        return $part;
    }
}
