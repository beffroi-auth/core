<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use Beffroi\Core\Domain\Credential\Attestation\VerifiedRegistration;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\PasskeyUserHandle;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Beffroi\Core\Port\PasskeyRegistrarInterface;
use Psr\Clock\ClockInterface;
use function random_bytes;
use function sprintf;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthn Level 3 §7.1
 *
 * User verification is asked for and not required, so that a hardware key without a PIN enrols.
 */
final readonly class WebauthnPasskeyRegistrar implements PasskeyRegistrarInterface
{
    public function __construct(
        private WebauthnCeremonies $ceremonies,
        private ClockInterface $clock,
    ) {
    }

    public function creationOptions(User $user, PasskeyUserHandle $userHandle, array $existing): PublicKeyCredentialCreationOptions
    {
        $tenant = $user->tenant;

        return PublicKeyCredentialCreationOptions::create(
            // No name: deprecated by webauthn-lib 5.3, the JSON carries the relying party identifier as the name.
            PublicKeyCredentialRpEntity::create('', WebauthnCeremonies::relyingPartyId($tenant)),
            PublicKeyCredentialUserEntity::create($user->username, $userHandle->value, $user->profile->name ?? $user->username),
            random_bytes(WebauthnCeremonies::CHALLENGE_LENGTH),
            $this->ceremonies->parameters(),
            AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            $tenant->passkeyAttestation->policy->conveyancePreference(),
            array_map(static fn (PasskeyCredential $passkey): PublicKeyCredentialDescriptor => $passkey
                ->record()
                ->getPublicKeyCredentialDescriptor(), $existing),
        );
    }

    public function verifyAttestation(Tenant $tenant, PublicKeyCredentialCreationOptions $options, string $response): VerifiedRegistration
    {
        $credential = $this->ceremonies->credentialFromJson($response);
        $attestation = $credential->response;
        if (! $attestation instanceof AuthenticatorAttestationResponse) {
            throw new PasskeyCeremonyFailedException('The response of the browser is not the attestation of a new passkey.');
        }
        // The library would read the FIDO metadata for options that ask for an attestation and refuse an AAGUID they do
        // not know (web-auth/webauthn-framework#1002): the policy of the tenant reads them instead.
        $withoutMetadata = clone $options;
        $withoutMetadata->attestation = PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;
        try {
            $record = $this->ceremonies->attestationValidator($tenant)->check($attestation, $withoutMetadata, WebauthnCeremonies::relyingPartyId($tenant));
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The attestation was refused: %s', $exception->getMessage()), previous: $exception);
        }
        $seen = $this->ceremonies->attestation($tenant, $attestation, $record, $options);

        return new VerifiedRegistration($record, $tenant->passkeyAttestation->judge($seen, $this->clock->now()));
    }
}
