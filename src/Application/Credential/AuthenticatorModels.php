<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Domain\Credential\ModelDeclaringCredentialInterface;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AuthenticatorModelSourceInterface;
use Symfony\Component\Uid\NilUuid;

/**
 * The make and model of the authenticator a credential declared (WebAuthn Level 3 §6), for the page that lists
 * the passkeys of a person and for the console (architecture §3.4): the sources asked in the order they are
 * wired, the first that names the AAGUID answering, nothing when none does. §6 lets a relying party "use the
 * AAGUID to attempt to identify the maker of the authenticator without requesting and verifying attestation",
 * which is what this does, and adds that "the AAGUID is not provably authentic without attestation": a model
 * is what the authenticator said of itself, and nothing decides a login on it.
 *
 * **An AAGUID of sixteen zero bytes names no model**, whatever a source says of it: it is what a client of
 * WebAuthn Level 2 sent in place of the real one when the provider asked for no attestation, a replacement
 * Level 3 dropped (§18.1.1).
 */
final readonly class AuthenticatorModels
{
    /**
     * @param iterable<AuthenticatorModelSourceInterface> $sources in the order they are asked
     */
    public function __construct(
        private iterable $sources,
    ) {
    }

    /**
     * @throws RuntimeException when what a source holds cannot be read
     */
    public function of(ModelDeclaringCredentialInterface $credential): ?AuthenticatorModel
    {
        if ($credential->aaguid->equals(new NilUuid())) {
            return null;
        }
        foreach ($this->sources as $source) {
            $model = $source->model($credential->aaguid);
            if ($model !== null) {
                return $model;
            }
        }

        return null;
    }
}
