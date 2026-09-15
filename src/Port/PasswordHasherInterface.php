<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\PasswordAlgorithm;
use Heidi\Core\Domain\Credential\PasswordHash;
use SensitiveParameter;

interface PasswordHasherInterface
{
    public function supports(PasswordAlgorithm $algorithm): bool;

    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash;

    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * Whether the hash should be recomputed after a successful verification, because its algorithm or its
     * parameters are weaker than what the hasher produces today.
     */
    public function needsRehash(PasswordHash $hash): bool;
}
