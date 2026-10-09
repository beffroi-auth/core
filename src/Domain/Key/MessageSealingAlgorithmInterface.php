<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * One algorithm a key pair the tenant seals what leaves the process to is generated for (decision 0020):
 * the body of every message this provider writes to a transport goes out as a JWE to the public half of
 * such a key, so that what a broker, or the table of a transport, keeps is worth nothing without the KMS.
 *
 * The tenant keeps it: it is of use "enc" (RFC 7517 §4.2) and it is not in the JWK Set, because a key the
 * discovery document publishes is a key under which any reader of it could seal a body the consumer would
 * open. For the same reason it names itself rather than naming a JOSE algorithm, as the key the tenant
 * hands out does (ApplicationServerAlgorithmInterface): a tenant holds one active key per algorithm
 * (TenantKeyRepositoryInterface::findActive()), so a pair answering to the name of a published algorithm
 * would be the pair clients encrypt to doing both jobs. What a sealed body carries in its "alg" and its
 * "enc" is the JOSE pair the sealing is done with, which the bridge of the library names.
 *
 * A key of the tenant in every other way: drawn by the generator, sealed by the KMS, staged, active,
 * retiring and retired on the same four steps, rotated by the same commands (architecture §3.3). A
 * retiring key still opens what was sealed to it while a message of that vintage may still be waiting,
 * which is what bounds its retirement window like any other.
 */
interface MessageSealingAlgorithmInterface extends KeyAlgorithmInterface
{
}
