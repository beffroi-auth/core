<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Exception\LogicException;
use SensitiveParameter;

interface PasswordHasherInterface
{
    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash;

    /**
     * @throws LogicException when no hasher handles the algorithm the hash names
     */
    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * @throws LogicException when no hasher handles the algorithm the hash names
     */
    public function needsRehash(PasswordHash $hash): bool;
}
