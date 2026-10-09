<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;

/**
 * One algorithm the tenant seals what leaves the process to, as web-token/jwt-library knows it: the key
 * pair it draws, and the JOSE pair a sealed body carries in its "alg" and its "enc".
 *
 * The two are not the same name. The key pair is named by what the tenant keeps it for, so that it is
 * never the pair clients encrypt to (MessageSealingAlgorithmInterface); the JWE is named by what JOSE
 * calls what seals it, because that is what opens it. The pair comes from the registries the provider is
 * wired with, so the sealing is done with an algorithm the provider already announces it can open and
 * nothing of its own.
 */
interface JoseMessageSealingAlgorithmInterface extends JoseKeyAlgorithmInterface, MessageSealingAlgorithmInterface
{
    /**
     * The "alg" of a sealed body: what wraps, or agrees on, its content encryption key (RFC 7518 §4).
     */
    public function keyEncryption(): JoseKeyEncryptionAlgorithmInterface;

    /**
     * The "enc" of a sealed body: what encrypts it (RFC 7518 §5).
     */
    public function contentEncryption(): JoseContentEncryptionAlgorithmInterface;
}
