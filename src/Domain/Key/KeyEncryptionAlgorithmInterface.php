<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * RFC 7518 §4
 *
 * Never RSA1_5 (RFC 8725 §3.4), nor "dir", which needs a shared secret where this key is published.
 */
interface KeyEncryptionAlgorithmInterface extends PublishedKeyAlgorithmInterface
{
}
