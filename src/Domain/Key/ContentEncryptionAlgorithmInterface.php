<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use Heidi\Core\Domain\Algorithm\NamedAlgorithmInterface;

/**
 * One JWE content encryption algorithm the provider opens a payload with (RFC 7518 §5): the "enc" of the
 * header. It takes no key of the tenant, the content encryption key being the one the "alg" wrapped or
 * agreed on, so it is not a KeyAlgorithmInterface; what the provider announces in
 * "request_object_encryption_enc_values_supported" is the list of those wired.
 */
interface ContentEncryptionAlgorithmInterface extends NamedAlgorithmInterface
{
}
