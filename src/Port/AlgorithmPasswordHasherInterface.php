<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\PasswordAlgorithm;
use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Exception\LogicException;
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
    public function supports(PasswordAlgorithm $algorithm): bool;

    /**
     * @throws LogicException when the algorithm of the hash is not supported: the registry was bypassed
     */
    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * @throws LogicException when the algorithm of the hash is not supported: the registry was bypassed
     */
    public function needsRehash(PasswordHash $hash): bool;
}
