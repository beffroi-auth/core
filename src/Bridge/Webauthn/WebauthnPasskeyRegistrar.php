<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\PasskeyUserHandle;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Beffroi\Core\Port\PasskeyRegistrarInterface;
use function random_bytes;
use function sprintf;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * The registration ceremony (WebAuthn Level 3 §7.1) on webauthn-lib: the relying party is named by its
 * identifier alone (§5.4.2), a passkey is a discoverable credential (residentKey required, §5.4.6), user
 * verification is asked for and not required, so that a hardware key without a PIN enrols, and no
 * attestation is asked for (§5.4.7).
 */
final readonly class WebauthnPasskeyRegistrar implements PasskeyRegistrarInterface
{
    public function __construct(
        private WebauthnCeremonies $ceremonies,
    ) {
    }

    public function creationOptions(User $user, PasskeyUserHandle $userHandle, array $existing): PublicKeyCredentialCreationOptions
    {
        $tenant = $user->tenant;

        return PublicKeyCredentialCreationOptions::create(
            // no name: deprecated by webauthn-lib 5.3, the JSON carries the relying party identifier as the name
            PublicKeyCredentialRpEntity::create('', WebauthnCeremonies::relyingPartyId($tenant)),
            PublicKeyCredentialUserEntity::create($user->username, $userHandle->value, $user->profile->name ?? $user->username),
            random_bytes(WebauthnCeremonies::CHALLENGE_LENGTH),
            WebauthnCeremonies::parameters(),
            AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            array_map(static fn (PasskeyCredential $passkey): PublicKeyCredentialDescriptor => $passkey
                ->record()
                ->getPublicKeyCredentialDescriptor(), $existing),
        );
    }

    public function verifyAttestation(Tenant $tenant, PublicKeyCredentialCreationOptions $options, string $response): CredentialRecord
    {
        $credential = $this->ceremonies->credentialFromJson($response);
        if (! $credential->response instanceof AuthenticatorAttestationResponse) {
            throw new PasskeyCeremonyFailedException('The response of the browser is not the attestation of a new passkey.');
        }
        try {
            return $this->ceremonies->attestationValidator($tenant)->check($credential->response, $options, WebauthnCeremonies::relyingPartyId($tenant));
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The attestation was refused: %s', $exception->getMessage()), previous: $exception);
        }
    }
}
