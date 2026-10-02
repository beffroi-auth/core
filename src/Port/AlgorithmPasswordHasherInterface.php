<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Exception\LogicException;
use SensitiveParameter;

/**
 * A hasher of named algorithms, what the registry routes a hash to: the native one of PHP, or the one an
 * import brings for the hashes of a previous provider. Nothing but the registry calls one directly; a use
 * case sees PasswordHasherInterface only.
 */
interface AlgorithmPasswordHasherInterface extends PasswordHasherInterface
{
    /**
     * Verifies and judges hashes of that algorithm; the registry asks before handing one over.
     */
    public function supports(PasswordAlgorithmInterface $algorithm): bool;

    /**
     * @throws LogicException when the algorithm of the hash is not supported: the registry was bypassed
     */
    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * @throws LogicException when the algorithm of the hash is not supported: the registry was bypassed
     */
    public function needsRehash(PasswordHash $hash): bool;
}
