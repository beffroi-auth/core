<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidJweException;
use Heidi\Core\Exception\KmsUnavailableException;

interface JweDecrypterInterface
{
    /**
     * Opens a compact JWE (RFC 7516 §7.1) encrypted to a published key of the tenant.
     *
     * @throws InvalidJweException when the token is not a compact JWE this provider opens: an "alg" or an
     *                             "enc" it is not wired with, no "kid", a key it does not publish, a key
     *                             that signs, a key that has retired, or a payload that does not open
     * @throws KmsUnavailableException when the KMS does not answer to open the key the token names
     */
    public function decrypt(Tenant $tenant, string $compactJwe): string;
}
