<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Webauthn;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\PasskeyCeremonyFailedException;
use Psr\Clock\ClockInterface;
use function sprintf;
use function strlen;
use function substr;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialParameters;

/**
 * What the two ceremonies of WebAuthn Level 3 share, on webauthn-lib (docs/09-briques.md): the relying
 * party is the tenant (its identifier the host of the issuer, §5.4.2, the one origin allowed the origin of
 * the issuer, §13.4.9, plain HTTP on a loopback host only because the issuer is), the algorithms are those
 * the provider verifies, the attestation formats those the library verifies without a metadata service
 * (the statement of a format it does not know is a refusal, never a pass), and the sign count is left to
 * the core (PasskeyCredential::signCountRegresses()), which tells a regression instead of refusing it.
 */
final class WebauthnCeremonies
{
    /**
     * COSE algorithm identifiers (RFC 9053 §2.1, §2.2, RFC 8812 §2): what a platform authenticator and a
     * hardware key create, in the order the provider prefers.
     */
    private const array ALGORITHMS = [-7, -257];

    private ?SerializerInterface $serializer = null;

    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return non-empty-list<PublicKeyCredentialParameters>
     */
    public static function parameters(): array
    {
        return [PublicKeyCredentialParameters::createPk(self::ALGORITHMS[0]), PublicKeyCredentialParameters::createPk(self::ALGORITHMS[1])];
    }

    /**
     * The relying party identifier of the tenant (WebAuthn Level 3 §5.4.2).
     */
    public static function relyingPartyId(Tenant $tenant): string
    {
        return $tenant->issuer->host;
    }

    /**
     * The one origin a ceremony of the tenant accepts (§13.4.9): the issuer without its path.
     */
    public static function origin(Tenant $tenant): string
    {
        $issuer = $tenant->issuer->value;

        return substr($issuer, 0, strlen($issuer) - strlen($tenant->issuer->path));
    }

    public function attestationValidator(Tenant $tenant): AuthenticatorAttestationResponseValidator
    {
        return AuthenticatorAttestationResponseValidator::create($this->factory($tenant)->creationCeremony());
    }

    public function assertionValidator(Tenant $tenant): AuthenticatorAssertionResponseValidator
    {
        return AuthenticatorAssertionResponseValidator::create($this->factory($tenant)->requestCeremony());
    }

    /**
     * The options in the JSON form the browser parses (§5.1.8, parseCreationOptionsFromJSON and
     * parseRequestOptionsFromJSON): the byte strings in base64url, nothing null.
     */
    public function optionsToJson(PublicKeyCredentialOptions $options): string
    {
        return $this->serializer()
            ->serialize($options, JsonEncoder::FORMAT, [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            ]);
    }

    /**
     * The credential the browser serialised (§5.1, toJSON), of either ceremony; anything the library
     * cannot read is a failure of the ceremony, whatever it says.
     *
     * @throws PasskeyCeremonyFailedException
     */
    public function credentialFromJson(string $json): PublicKeyCredential
    {
        try {
            return $this->serializer()
                ->deserialize($json, PublicKeyCredential::class, JsonEncoder::FORMAT);
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The response of the browser cannot be read: %s', $exception->getMessage()), previous: $exception);
        }
    }

    private function factory(Tenant $tenant): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAlgorithmManager(self::algorithms());
        $factory->setAttestationStatementSupportManager($this->attestationFormats());
        // the whole origin, scheme and port included: what lets a loopback issuer be plain HTTP, as the Issuer does
        $factory->setAllowedOrigins([self::origin($tenant)]);
        $factory->setCounterChecker(new IgnoredCounterChecker());

        return $factory;
    }

    private function serializer(): SerializerInterface
    {
        return $this->serializer ??= new WebauthnSerializerFactory($this->attestationFormats())
            ->create();
    }

    private static function algorithms(): Manager
    {
        return Manager::create()
            ->add(ES256::create(), RS256::create());
    }

    /**
     * Verified when the authenticator sends one, never required (the spike README): "none" is what the
     * provider asks for, the others are what an authenticator may send anyway.
     */
    private function attestationFormats(): AttestationStatementSupportManager
    {
        return new AttestationStatementSupportManager([
            new NoneAttestationStatementSupport(),
            PackedAttestationStatementSupport::create(self::algorithms()),
            FidoU2FAttestationStatementSupport::create(),
            AndroidKeyAttestationStatementSupport::create(),
            TPMAttestationStatementSupport::create($this->clock),
            AppleAttestationStatementSupport::create(),
        ]);
    }
}
