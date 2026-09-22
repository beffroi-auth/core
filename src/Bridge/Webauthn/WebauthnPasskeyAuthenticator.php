<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Webauthn;

use function array_map;
use Heidi\Core\Domain\Credential\PasskeyCredential;
use Heidi\Core\Domain\Credential\VerifiedPasskeyAssertion;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\PasskeyCeremonyFailedException;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasskeyAuthenticatorInterface;
use function random_bytes;
use function sprintf;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * The authentication ceremony (WebAuthn Level 3 §7.2) on webauthn-lib: the passkey is the one of the
 * tenant the response names, revoked or not (the core tells it apart after the signature, so that a
 * refusal costs the same); user verification is asked for and not required, the flags say what was done.
 */
final readonly class WebauthnPasskeyAuthenticator implements PasskeyAuthenticatorInterface
{
    /**
     * WebAuthn Level 3 §13.4.3: at least 16 bytes.
     */
    private const int CHALLENGE_LENGTH = 32;

    public function __construct(
        private WebauthnCeremonies $ceremonies,
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    public function requestOptions(Tenant $tenant, array $allowed): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            random_bytes(self::CHALLENGE_LENGTH),
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
        try {
            $this->ceremonies->assertionValidator($tenant)->check(
                $passkey->record(),
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
