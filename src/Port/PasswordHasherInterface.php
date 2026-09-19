<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\PasswordHash;
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

    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * Weaker than what the hasher produces today.
     */
    public function needsRehash(PasswordHash $hash): bool;
}
