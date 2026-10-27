<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * A key of the tenant that signs, opened by the KMS, and what signs under it: the second half of a signature, the
 * one that asks nothing of the KMS (architecture §3.3). Whoever holds it holds a way to sign and not the key, whose
 * private half stays in the bridge that opened it, as every UnsealedKey does.
 */
interface OpenedSigningKeyInterface
{
    /**
     * The compact serialization (RFC 7515 §7.1); "alg" and "kid" are those of the key, the rest the caller's.
     *
     * @param array<string, mixed> $protectedHeader
     */
    public function sign(string $payload, array $protectedHeader = []): string;
}
