<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_is_list;
use function array_slice;
use function base64_decode;
use Beffroi\Core\Exception\MetadataBlobRefusedException;
use function count;
use function is_array;
use function is_string;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use function sprintf;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\MetadataService\CertificateChain\CertificateChainValidator;
use Webauthn\MetadataService\CertificateChain\CertificateToolbox;
use Webauthn\MetadataService\Service\MetadataBLOBPayload;

/**
 * Verifies a Metadata BLOB as the FIDO Metadata Service v3.1.1 (§3.1.7, §3.2) has a relying party verify
 * it: a compact JWS with one signature, whose protected header carries the certificate chain of the
 * signer (RFC 7515 §4.1.6, "x5c"), the signature checked with the key of the first certificate, the chain
 * checked up to the root the caller pins (never one the token names), the payload read as a blob. A
 * certificate of the chain that carries the name and the key of the pinned root is that root issued by
 * another, and the chain stops before it (upToThePinnedRoot()). What the library ships for this
 * (FidoAllianceCompliantMetadataService) fetches the root over HTTP at every load and swallows a refusal
 * into an empty repository; this one refuses aloud and reads nothing from the network, the console does
 * that once.
 */
final readonly class MetadataBlobVerifier
{
    private SerializerInterface $serializer;

    private JWSVerifier $verifier;

    public function __construct(
        private CertificateChainValidator $chains,
    ) {
        $this->serializer = new WebauthnSerializerFactory(AttestationStatementSupportManager::create())->create();
        // what the FIDO Alliance signs with today (RS256) and what a test service may (ES256), nothing else
        $this->verifier = new JWSVerifier(new AlgorithmManager([new ES256(), new RS256()]));
    }

    /**
     * @param string $rootCertificate the pinned root, PEM
     *
     * @throws MetadataBlobRefusedException
     */
    public function verify(string $token, string $rootCertificate): MetadataBlob
    {
        $jws = $this->parse($token);
        $chain = self::chain($jws);
        try {
            $key = JWKFactory::createFromX5C($chain);
            $holds = $this->verifier->verifyWithKey($jws, $key, 0);
        } catch (Throwable $exception) {
            throw new MetadataBlobRefusedException(sprintf('The signature of the blob cannot be checked: %s', $exception->getMessage()), previous: $exception);
        }
        if (! $holds) {
            throw new MetadataBlobRefusedException('The signature of the blob does not hold under the certificate it names.');
        }
        try {
            $this->chains->check(
                CertificateToolbox::fixPEMStructures(self::upToThePinnedRoot($chain, $rootCertificate)),
                [CertificateToolbox::fixPEMStructure($rootCertificate)],
            );
        } catch (Throwable $exception) {
            throw new MetadataBlobRefusedException(
                sprintf('The certificate chain of the blob does not lead to the pinned root: %s', $exception->getMessage()),
                previous: $exception,
            );
        }
        $payload = $jws->getPayload();
        if ($payload === null || $payload === '') {
            throw new MetadataBlobRefusedException('The blob has no payload.');
        }
        try {
            $content = $this->serializer->deserialize($payload, MetadataBLOBPayload::class, JsonEncoder::FORMAT);
        } catch (Throwable $exception) {
            throw new MetadataBlobRefusedException(
                sprintf('The payload of the blob is not a Metadata BLOB payload: %s', $exception->getMessage()),
                previous: $exception,
            );
        }

        return new MetadataBlob($payload, $content);
    }

    /**
     * @throws MetadataBlobRefusedException
     */
    private function parse(string $token): JWS
    {
        try {
            $jws = new CompactSerializer()->unserialize($token);
        } catch (Throwable $exception) {
            throw new MetadataBlobRefusedException(sprintf('The blob is not a compact JWS: %s', $exception->getMessage()), previous: $exception);
        }
        if ($jws->countSignatures() !== 1) {
            throw new MetadataBlobRefusedException(sprintf('The blob carries %d signatures where one is expected.', $jws->countSignatures()));
        }

        return $jws;
    }

    /**
     * The chain up to the pinned root. A trust anchor is a name and a key (RFC 5280 §6.1.1 (d)), so a certificate
     * after the signer that carries the name and the key of the pinned root is that root, issued by another
     * one: the chain stops before it, and the pinned root takes its place. The chain of the FIDO Alliance ends
     * with GlobalSign Root R46 issued by GlobalSign Root CA - R3, and verifies under GlobalSign Root R46, the
     * root the FIDO Alliance names, once that certificate is left out. A certificate this cannot read stays
     * where it is, for the chain to be refused on it.
     *
     * @param non-empty-list<string> $chain
     *
     * @return list<string>
     */
    private static function upToThePinnedRoot(array $chain, string $rootCertificate): array
    {
        try {
            $root = Certificate::fromPEM(PEM::fromString(CertificateToolbox::fixPEMStructure($rootCertificate)))->tbsCertificate();
        } catch (Throwable) {
            return $chain;
        }
        for ($position = 1, $length = count($chain); $position < $length; ++$position) {
            $der = base64_decode($chain[$position], true);
            if ($der === false) {
                return $chain;
            }
            try {
                $certificate = Certificate::fromDER($der)->tbsCertificate();
            } catch (Throwable) {
                return $chain;
            }
            if ($certificate->subject()->equals($root->subject()) && $certificate->subjectPublicKeyInfo()->toDER() === $root->subjectPublicKeyInfo()->toDER()) {
                return array_slice($chain, 0, $position);
            }
        }

        return $chain;
    }

    /**
     * The chain of the signer, the signing certificate first (RFC 7515 §4.1.6).
     *
     * @throws MetadataBlobRefusedException
     * @return non-empty-list<string>
     */
    private static function chain(JWS $jws): array
    {
        $header = $jws->getSignature(0)->getProtectedHeader();
        $x5c = $header['x5c'] ?? null;
        if (! is_array($x5c) || $x5c === [] || ! array_is_list($x5c)) {
            throw new MetadataBlobRefusedException('The protected header of the blob names no certificate chain ("x5c").');
        }
        foreach ($x5c as $certificate) {
            if (! is_string($certificate) || $certificate === '') {
                throw new MetadataBlobRefusedException('The certificate chain of the blob is not a list of certificates.');
            }
        }

        return $x5c;
    }
}
