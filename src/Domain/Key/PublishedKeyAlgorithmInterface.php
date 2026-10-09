<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * One algorithm whose keys the tenant publishes in its JWK Set (RFC 7517 §5): what a relying party reads
 * to verify what this issuer signed and to encrypt what it sends it. A key of such an algorithm is in that
 * set for as long as it is in service, and a consumer that reads the set is entitled to use it for what
 * its "use" says (§4.2).
 *
 * A key of any other algorithm is one the tenant keeps: the key pair it hands out itself
 * (ApplicationServerAlgorithmInterface) and the key it seals what leaves the process to
 * (MessageSealingAlgorithmInterface, decision 0020). The distinction is read here and nowhere else, so
 * that an algorithm added to either family needs no line anywhere: what is published is derived from the
 * interface the algorithm implements, never from a list of the keys that are not.
 *
 * It is a marker and not a flag on KeyAlgorithmInterface: whether a key is published decides nothing a
 * key does, so nothing calls it, and a boolean nothing calls is a boolean that drifts.
 */
interface PublishedKeyAlgorithmInterface extends KeyAlgorithmInterface
{
}
