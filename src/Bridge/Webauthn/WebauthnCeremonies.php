<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Psr\Clock\ClockInterface;
use function sprintf;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialParameters;

final readonly class WebauthnCeremonies
{
    /**
     * WebAuthn Level 3 §13.4.3
     */
    public const int CHALLENGE_LENGTH = 32;

    private SerializerInterface $serializer;

    public function __construct(
        private PasskeyAlgorithms $algorithms,
        private ClockInterface $clock,
        private MetadataStatementRepository $statements,
        private StatusReportRepository $statusReports,
        private CertificateChainValidator $chains,
    ) {
        $this->serializer = new WebauthnSerializerFactory($this->attestationFormats())->create();
    }

    /**
     * @return non-empty-list<PublicKeyCredentialParameters> in the order of preference
     */
    public function parameters(): array
    {
        return $this->algorithms->parameters();
    }

    /**
     * WebAuthn Level 3 §5.4.2
     */
    public static function relyingPartyId(Tenant $tenant): string
    {
        return $tenant->issuer->host;
    }

    public function attestationValidator(Tenant $tenant): AuthenticatorAttestationResponseValidator
    {
        return AuthenticatorAttestationResponseValidator::create($this->factory($tenant)->creationCeremony());
    }

    /**
     * WebAuthn Level 3 §7.1 steps 19 to 22
     */
    public function attestation(
        Tenant $tenant,
        AuthenticatorAttestationResponse $response,
        CredentialRecord $record,
        PublicKeyCredentialCreationOptions $options,
    ): WebauthnAttestation {
        return new WebauthnAttestation($response, $record, $options, self::relyingPartyId($tenant), $this->statements, $this->statusReports, $this->chains);
    }

    public function assertionValidator(Tenant $tenant): AuthenticatorAssertionResponseValidator
    {
        return AuthenticatorAssertionResponseValidator::create($this->factory($tenant)->requestCeremony());
    }

    /**
     * WebAuthn Level 3 §5.1.8
     */
    public function optionsToJson(PublicKeyCredentialOptions $options): string
    {
        return $this->serializer->serialize($options, JsonEncoder::FORMAT, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
    }

    /**
     * WebAuthn Level 3 §5.1
     *
     * @throws PasskeyCeremonyFailedException
     */
    public function credentialFromJson(string $json): PublicKeyCredential
    {
        try {
            return $this->serializer->deserialize($json, PublicKeyCredential::class, JsonEncoder::FORMAT);
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The response of the browser cannot be read: %s', $exception->getMessage()), previous: $exception);
        }
    }

    private function factory(Tenant $tenant): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAlgorithmManager($this->algorithms->verifiers());
        $factory->setAttestationStatementSupportManager($this->attestationFormats());
        // The whole origin, scheme and port included: it is what lets a loopback issuer be plain HTTP.
        $factory->setAllowedOrigins([$tenant->issuer->origin()]);
        $factory->setCounterChecker(new IgnoredCounterChecker());
        $factory->enableMetadataStatementSupport($this->statements, $this->statusReports, $this->chains);

        return $factory;
    }

    private function attestationFormats(): AttestationStatementSupportManager
    {
        return new AttestationStatementSupportManager(array_map($this->algorithms->attestation(...), [
            PackedAttestationStatementSupport::create($this->algorithms->verifiers()),
            FidoU2FAttestationStatementSupport::create(),
            AndroidKeyAttestationStatementSupport::create(),
            $this->algorithms->tpm($this->clock),
            AppleAttestationStatementSupport::create(),
        ]));
    }
}
