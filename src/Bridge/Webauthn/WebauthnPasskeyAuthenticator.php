<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\VerifiedPasskeyAssertion;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\PasskeyAuthenticatorInterface;
use function random_bytes;
use function sprintf;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * WebAuthn Level 3 §7.2
 *
 * A revoked passkey is verified too, and the library is handed the record without its BE flag: the core
 * refuses either after the signature, so that a refusal costs the same and names the passkey.
 */
final readonly class WebauthnPasskeyAuthenticator implements PasskeyAuthenticatorInterface
{
    public function __construct(
        private WebauthnCeremonies $ceremonies,
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    public function requestOptions(Tenant $tenant, array $allowed): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            random_bytes(WebauthnCeremonies::CHALLENGE_LENGTH),
            WebauthnCeremonies::relyingPartyId($tenant),
            array_map(static fn (PasskeyCredential $passkey): PublicKeyCredentialDescriptor => $passkey
                ->record()
                ->getPublicKeyCredentialDescriptor(), $allowed),
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
        );
    }

    public function verifyAssertion(Tenant $tenant, PublicKeyCredentialRequestOptions $options, string $response): VerifiedPasskeyAssertion
    {
        $credential = $this->ceremonies->credentialFromJson($response);
        if (! $credential->response instanceof AuthenticatorAssertionResponse) {
            throw new PasskeyCeremonyFailedException('The response of the browser is not the assertion of a passkey.');
        }
        $passkey = $this->credentials->findByExternalId(
            $tenant,
            PasskeyCredential::class,
            $credential->rawId,
        ) ?? throw new PasskeyCeremonyFailedException('The response names no passkey of the tenant.');
        $record = $passkey->record();
        $record->backupEligible = null;
        try {
            $this->ceremonies->assertionValidator($tenant)->check(
                $record,
                $credential->response,
                $options,
                WebauthnCeremonies::relyingPartyId($tenant),
                null,
            );
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The assertion was refused: %s', $exception->getMessage()), previous: $exception);
        }

        return new VerifiedPasskeyAssertion($passkey, $credential->response->authenticatorData);
    }
}
