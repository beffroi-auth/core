<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

/**
 * A credential a party presents by an identifier of its own before anything is verified: the credential
 * ID of WebAuthn for a passkey. Found by CredentialRepositoryInterface::findByExternalId(); unique in
 * the tenant.
 */
interface ExternallyIdentifiedCredentialInterface
{
    public string $externalId { get; }
}
