<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Otphp;

use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Credential\TotpSecret;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\TotpAlgorithmInterface;
use function intdiv;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use const PHP_INT_MAX;
use Psr\Clock\ClockInterface;
use function sprintf;
use function str_replace;

/**
 * RFC 6238, RFC 4226 §5.3
 */
final readonly class OtphpTotpAlgorithm implements TotpAlgorithmInterface
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    /**
     * RFC 6238 §4.2
     */
    public function codeAt(TotpSecret $secret, int $timeStep): string
    {
        if ($timeStep < 0 || $timeStep > intdiv(PHP_INT_MAX, TotpCredential::PERIOD)) {
            throw new InvalidArgumentException('A time step is never negative (RFC 6238 §4.2), nor beyond what a Unix time counts.');
        }
        $unixTime = $timeStep * TotpCredential::PERIOD;

        return $this->totp($secret)->at($unixTime);
    }

    /**
     * The label is "issuer:account" (Key Uri Format), so a colon in either is shown as a space.
     */
    public function provisioningUri(TotpSecret $secret, string $issuer, string $account): string
    {
        return $this->totp($secret)->withIssuer(self::labelPart($issuer, 'issuer'))->withLabel(self::labelPart($account, 'account'))->getProvisioningUri();
    }

    private function totp(TotpSecret $secret): TOTP
    {
        /** @var non-empty-string $encoded */
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
