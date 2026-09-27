<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * One algorithm a key pair the tenant hands out itself is generated for, rather than one it publishes in
 * its JWK Set (RFC 7517 §5). Voluntary Application Server Identification is the one there is (RFC 8292):
 * the browser is given the public half when it subscribes to a push service, and the subscription it gets
 * back is bound to that half and to nothing else (§3.2); a relying party of this provider has no use for
 * it and no token of this provider ever names its "kid".
 *
 * A key of the tenant in every other way: drawn by the generator, sealed by the KMS, staged, active,
 * retiring and retired on the same four steps (architecture §3.3). That is what a rotation of such a key
 * has to be, and not a new pair in a file: a subscription made under the previous key goes on being
 * pushed to until that key retires, and a browser that has not subscribed again by then subscribes again.
 */
interface ApplicationServerAlgorithmInterface extends KeyAlgorithmInterface
{
}
