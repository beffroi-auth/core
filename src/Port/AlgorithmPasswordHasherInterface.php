<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Exception\LogicException;
use SensitiveParameter;

interface AlgorithmPasswordHasherInterface extends PasswordHasherInterface
{
    public function supports(PasswordAlgorithmInterface $algorithm): bool;

    /**
     * @throws LogicException when the algorithm of the hash is not supported
     */
    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool;

    /**
     * @throws LogicException when the algorithm of the hash is not supported
     */
    public function needsRehash(PasswordHash $hash): bool;
}
