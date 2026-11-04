<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\TotpSecret;
use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * RFC 6238
 */
interface TotpAlgorithmInterface
{
    /**
     * RFC 6238 §4.2
     *
     * @param int $timeStep periods since the Unix epoch
     *
     * @throws InvalidArgumentException when the step is negative, or beyond what a Unix time counts
     */
    public function codeAt(TotpSecret $secret, int $timeStep): string;

    /**
     * Key Uri Format (Google Authenticator), RFC 4648 §6
     *
     * @throws InvalidArgumentException when the issuer or the account is empty once its colons are taken out
     */
    public function provisioningUri(TotpSecret $secret, string $issuer, string $account): string;
}
