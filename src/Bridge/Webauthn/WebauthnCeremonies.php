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
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialParameters;

/**
 * What the two ceremonies of WebAuthn Level 3 share, on webauthn-lib (docs/09-briques.md): the relying
 * party is the tenant (its identifier the host of the issuer, §5.4.2, the one origin allowed the origin of
 * the issuer, §13.4.9, plain HTTP on a loopback host only because the issuer is), the algorithms are those
 * of its policy (PasskeyAlgorithms: the provider's, or the conformance tools' on the endpoints of the tools
 * alone), the attestation formats those the library verifies as that policy runs them (the statement of a
 * format it does not know is a refusal, never a pass), the FIDO metadata those of the store (§7.1 step 22: consulted
 * by the library when the options asked for an attestation, which the provider's own enrolment never does,
 * §5.4.7, and which a ceremony asking for one gets: a compromised authenticator refused on its status
 * reports, a full attestation checked up to the root its statement names, an unknown AAGUID refused on a
 * full attestation and passed on a self attestation), and the sign count is left to the core
 * (PasskeyCredential::signCountRegresses()), which refuses a regression after the signature.
 */
final readonly class WebauthnCeremonies
{
    /**
     * WebAuthn Level 3 §13.4.3: at least 16 bytes. The same for either ceremony, so it is written here
     * and not once per adapter.
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
     * What the options of a creation ceremony offer, in the order of preference of the policy.
     *
     * @return non-empty-list<PublicKeyCredentialParameters>
     */
    public function parameters(): array
    {
        return $this->algorithms->parameters();
    }

    /**
     * The relying party identifier of the tenant (WebAuthn Level 3 §5.4.2).
     */
    public static function relyingPartyId(Tenant $tenant): string
    {
        return $tenant->issuer->host;
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
        return $this->serializer->serialize($options, JsonEncoder::FORMAT, [
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
            return $this->serializer->deserialize($json, PublicKeyCredential::class, JsonEncoder::FORMAT);
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The response of the browser cannot be read: %s', $exception->getMessage()), previous: $exception);
        }
    }

    private function factory(Tenant $tenant): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        // the signature of an assertion is verified with every algorithm a credential of the policy may hold
        $factory->setAlgorithmManager($this->algorithms->verifiers());
        $factory->setAttestationStatementSupportManager($this->attestationFormats());
        // the whole origin, scheme and port included: what lets a loopback issuer be plain HTTP, as the Issuer does
        $factory->setAllowedOrigins([$tenant->issuer->origin()]);
        $factory->setCounterChecker(new IgnoredCounterChecker());
        // read when a ceremony asked for an attestation; the chains of an attestation are checked with the
        // same validator as the chain of a blob, up to the roots the statement of the authenticator names
        $factory->enableMetadataStatementSupport($this->statements, $this->statusReports, $this->chains);

        return $factory;
    }

    /**
     * Verified when the authenticator sends one, never required (the spike README): "none" is what the
     * provider asks for, and what the manager always knows; the others are what an authenticator may send
     * anyway, and what a ceremony that asks for an attestation receives, each run as the policy runs it.
     */
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
