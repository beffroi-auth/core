<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\TotpSecret;
use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * RFC 6238 over a secret, with the parameters of TotpCredential: the code at a time step, and the URI an
 * authenticator enrols from. The core decides which steps to try and what an accepted code does; the
 * arithmetic of RFC 4226 §5 is the brick's.
 */
interface TotpAlgorithmInterface
{
    /**
     * RFC 6238 §4.2: TOTP = HOTP(K, T), T the time step.
     *
     * @param int $timeStep a non-negative count of periods since the Unix epoch
     *
     * @throws InvalidArgumentException when the step is negative, or beyond what a Unix time counts
     */
    public function codeAt(TotpSecret $secret, int $timeStep): string;

    /**
     * The otpauth URI of the Key Uri Format (Google Authenticator), "issuer:account" as its label and the
     * issuer as a parameter, with the secret in base32 (RFC 4648 §6).
     *
     * @throws InvalidArgumentException when the issuer or the account is empty once the colons the
     *                                  label cannot carry are taken out
     */
    public function provisioningUri(TotpSecret $secret, string $issuer, string $account): string;
}
