<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * RFC 8292
 *
 * Handed to a browser subscribing to a push service, never published in the JWK Set.
 */
interface ApplicationServerAlgorithmInterface extends KeyAlgorithmInterface
{
}
