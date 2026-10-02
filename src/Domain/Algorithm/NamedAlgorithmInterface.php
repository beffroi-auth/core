<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Algorithm;

/**
 * An algorithm the provider is wired with, under the name it is known by: the "alg" or the "enc" of a
 * JOSE header, the name a stored password hash carries. The one thing a registry needs of what it holds,
 * and the one thing a name is resolved against, so that holding a wiring and looking a name up in it is
 * written once whatever the wiring is about.
 *
 * What an algorithm does beyond naming itself belongs to the interface of its use; nothing reads a class
 * name, and a new algorithm is a class plus a line in the wiring.
 */
interface NamedAlgorithmInterface
{
    /**
     * @return non-empty-string
     */
    public function name(): string;
}
