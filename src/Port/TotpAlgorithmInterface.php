<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\TotpSecret;

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
     */
    public function codeAt(TotpSecret $secret, int $timeStep): string;

    /**
     * The otpauth URI of the Key Uri Format (Google Authenticator), "issuer:account" as its label and the
     * issuer as a parameter, with the secret in base32 (RFC 4648 §6).
     */
    public function provisioningUri(TotpSecret $secret, string $issuer, string $account): string;
}
