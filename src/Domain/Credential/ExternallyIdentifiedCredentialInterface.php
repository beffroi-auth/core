<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * A credential a party presents by an identifier of its own before anything is verified: the credential
 * ID of WebAuthn for a passkey. Found by CredentialRepositoryInterface::findByExternalId(); unique in
 * the tenant. The kind holds the value under this name, whatever the name its protocol gives it, so that
 * a store finds any such kind by the same field and names none.
 */
interface ExternallyIdentifiedCredentialInterface
{
    public string $externalId { get; }
}
