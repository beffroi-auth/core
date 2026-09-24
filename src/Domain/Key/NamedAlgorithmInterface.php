<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * Something JOSE names in a header: an algorithm the provider is wired with, whatever it does with a key
 * or without one. The one thing every registry of the wiring needs of what it holds, and the one thing a
 * name is resolved against, so that holding a wiring and looking a name up in it is written once.
 */
interface NamedAlgorithmInterface
{
    /**
     * The name JOSE gives it in a header: the "alg" of a JWS (RFC 7515 §4.1.1) or of a JWE
     * (RFC 7516 §4.1.1), the "enc" of a JWE (§4.1.2).
     *
     * @return non-empty-string
     */
    public function name(): string;
}
