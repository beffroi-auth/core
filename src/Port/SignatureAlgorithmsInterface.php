<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Heidi\Core\Exception\UnsupportedAlgorithmException;

/**
 * The signature algorithms the wiring gives the provider, the one source of truth over them: the keys it
 * generates, the JWS it signs, the names a client may register and what the discovery document announces
 * all read this one (issue #98). Nothing enumerates them in parallel.
 */
interface SignatureAlgorithmsInterface
{
    /**
     * @return non-empty-list<SignatureAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired signs under that name
     */
    public function named(string $name): SignatureAlgorithmInterface;

    /**
     * What signs when nobody named an algorithm: a client that registered no "id_token_signed_response_alg"
     * (OIDC Registration §2), an access token, a signed introspection response, an operator who named none.
     * The first wired one.
     */
    public function defaultAlgorithm(): SignatureAlgorithmInterface;
}
