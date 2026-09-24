<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Heidi\Core\Exception\UnsupportedAlgorithmException;

/**
 * The password algorithms the wiring gives the provider, the one source of truth over them: what a new
 * hash is made with, and what a stored hash is read back as. A name that nothing wired carries is a hash
 * this deployment cannot account for, and it is refused rather than guessed.
 */
interface PasswordAlgorithmsInterface
{
    /**
     * @return non-empty-list<PasswordAlgorithmInterface> in the order they are wired, the default first
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired carries that name
     */
    public function named(string $name): PasswordAlgorithmInterface;

    /**
     * What a new hash is made with: the first wired one.
     */
    public function defaultAlgorithm(): PasswordAlgorithmInterface;
}
