<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * The life of a signing key (architecture §3.3). Staged: published, so that a relying party caching the
 * JWK Set learns its "kid" before anything is signed with it (OIDC Core §10.1.1); signs nothing. Active:
 * signs; one per tenant and algorithm. Retiring: signs nothing, still published while a JWS it signed can
 * be alive. Retired: out of the JWK Set; a JWS naming its "kid" is refused from then on. Two windows: the
 * staging one, before activation, and the retirement one, after.
 */
enum KeyStatus: string
{
    case Staged = 'staged';
    case Active = 'active';
    case Retiring = 'retiring';
    case Retired = 'retired';

    public function isPublished(): bool
    {
        return $this !== self::Retired;
    }
}
