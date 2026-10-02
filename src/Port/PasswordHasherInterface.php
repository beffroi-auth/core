<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Exception\LogicException;
use SensitiveParameter;

/**
 * What a use case does with a password: hash a new one, verify one, and know when a stored hash is weaker
 * than what would be produced today. Which algorithm a hash carries is not its business: the
 * implementation routes it (PasswordHasherRegistry), the hashers of one algorithm are
 * AlgorithmPasswordHasherInterface.
 */
interface PasswordHasherInterface
{
    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash;

    /**
     * @throws LogicException when no hasher of this deployment handles the algorithm the hash names: an
     *                        import brought a hash nothing here can verify, which is a wiring to fix and
     *                        not an answer to give the user
     */
    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * Weaker than what the hasher produces today.
     *
     * @throws LogicException when no hasher of this deployment handles the algorithm the hash names
     */
    public function needsRehash(PasswordHash $hash): bool;
}
