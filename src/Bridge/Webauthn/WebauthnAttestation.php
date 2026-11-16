<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_key_last;
use function array_map;
use Beffroi\Core\Domain\Credential\Attestation\AttestationInterface;
use Beffroi\Core\Domain\Tenant\AttestationRoot;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use function bin2hex;
use function in_array;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\Certificate\Extension\UnknownExtension;
use function sprintf;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CeremonyStep\CheckMetadataStatement;
use Webauthn\CredentialRecord;
use Webauthn\Exception\MetadataServiceException;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\StatusReportRepository;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\TrustPath\CertificateTrustPath;

/**
 * WebAuthn Level 3 §6.5, §7.1 steps 19 to 22
 */
final readonly class WebauthnAttestation implements AttestationInterface
{
    /**
     * WebAuthn Level 3 §8.2.2, id-fido-gen-ce-sernum
     */
    private const string SERIAL_NUMBER = '1.3.6.1.4.1.45724.1.1.2';

    /**
     * WebAuthn Level 3 §6.5.3: the types whose certificate chain names who attests the model.
     */
    private const array ATTESTED_BY_A_CHAIN = [AttestationStatement::TYPE_BASIC, AttestationStatement::TYPE_ATTCA, AttestationStatement::TYPE_ANONCA];

    public Uuid $aaguid;

    public function __construct(
        private AuthenticatorAttestationResponse $response,
        private CredentialRecord $record,
        private PublicKeyCredentialCreationOptions $options,
        private string $relyingPartyId,
        private MetadataStatementRepository $statements,
        private StatusReportRepository $statusReports,
        private CertificateChainValidator $chains,
    ) {
        $this->aaguid = $record->aaguid;
    }

    /**
     * The step of the library runs for an AAGUID the metadata know only: it refuses one they do not know
     * (web-auth/webauthn-framework#1002), and it reads them for options that ask for an attestation only.
     */
    public function provesTheModel(): bool
    {
        if (
            ! in_array($this->response->attestationObject->attStmt->type, self::ATTESTED_BY_A_CHAIN, true)
            || $this->aaguid->equals(new NilUuid())
            || $this->statements->findOneByAAGUID($this->aaguid->toRfc4122()) === null
        ) {
            return false;
        }
        $step = new CheckMetadataStatement();
        $step->enableMetadataStatementSupport($this->statements, new NoStatusReports(), $this->chains);
        $askingForOne = clone $this->options;
        $askingForOne->attestation = PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT;
        try {
            $step->process($this->record, $this->response, $askingForOne, null, $this->relyingPartyId);
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(sprintf('The attestation was refused: %s', $exception->getMessage()), previous: $exception);
        }

        return true;
    }

    public function chainsUpTo(array $roots): bool
    {
        $path = $this->response->attestationObject->attStmt->trustPath;
        if ($roots === [] || ! $path instanceof CertificateTrustPath || $path->certificates === []) {
            return false;
        }
        try {
            $this->chains->check($path->certificates, array_map(static fn (AttestationRoot $root): string => $root->pem, $roots));
        } catch (MetadataServiceException) {
            return false;
        }

        return true;
    }

    public function serialNumber(): ?string
    {
        $path = $this->response->attestationObject->attStmt->trustPath;
        if (! $path instanceof CertificateTrustPath || $path->certificates === []) {
            return null;
        }
        $extensions = Certificate::fromPEM(PEM::fromString($path->certificates[0]))->tbsCertificate()->extensions();
        if (! $extensions->has(self::SERIAL_NUMBER)) {
            return null;
        }
        $extension = $extensions->get(self::SERIAL_NUMBER);
        if ($extension->isCritical()) {
            throw new PasskeyCeremonyFailedException('The attestation was refused: its serial number extension is marked critical (WebAuthn Level 3 §8.2.2).');
        }
        try {
            $serialNumber = $extension instanceof UnknownExtension
                ? UnspecifiedType::fromDER($extension->extensionValue())->asOctetString()->string()
                : '';
        } catch (Throwable $exception) {
            throw new PasskeyCeremonyFailedException(
                'The attestation was refused: its serial number is no OCTET STRING (WebAuthn Level 3 §8.2.2).',
                previous: $exception,
            );
        }
        if ($serialNumber === '') {
            throw new PasskeyCeremonyFailedException('The attestation was refused: its serial number names no authenticator (WebAuthn Level 3 §8.2.2).');
        }

        return bin2hex($serialNumber);
    }

    public function isUnderSecurityAlert(): bool
    {
        if ($this->aaguid->equals(new NilUuid())) {
            return false;
        }
        $reports = $this->statusReports->findStatusReportsByAAGUID($this->aaguid->toRfc4122());
        $last = array_key_last($reports);

        return $last !== null && $reports[$last]->isCompromised();
    }
}
