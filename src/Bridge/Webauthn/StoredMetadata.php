<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\MetadataBlobStoreInterface;
use function sprintf;
use function strtolower;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\MetadataService\MetadataStatementRepository;
use Webauthn\MetadataService\Service\MetadataBLOBPayload;
use Webauthn\MetadataService\Service\MetadataBLOBPayloadEntry;
use Webauthn\MetadataService\Statement\MetadataStatement;
use Webauthn\MetadataService\StatusReportRepository;

/**
 * WebAuthn Level 3 §7.1 step 22, FIDO Metadata Service v3 §3.2
 */
final class StoredMetadata implements MetadataStatementRepository, StatusReportRepository
{
    /**
     * @var array<string, MetadataBLOBPayloadEntry>|null
     */
    private ?array $entries = null;

    private readonly SerializerInterface $serializer;

    public function __construct(
        private readonly MetadataBlobStoreInterface $store,
    ) {
        $this->serializer = new WebauthnSerializerFactory(AttestationStatementSupportManager::create())->create();
    }

    public function findOneByAAGUID(string $aaguid): ?MetadataStatement
    {
        return $this->entries()[strtolower($aaguid)]->metadataStatement ?? null;
    }

    public function findStatusReportsByAAGUID(string $aaguid): array
    {
        return $this->entries()[strtolower($aaguid)]->statusReports ?? [];
    }

    /**
     * @return array<string, MetadataBLOBPayloadEntry>
     */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }
        $entries = [];
        foreach ($this->store->readAll() as $name => $payload) {
            foreach ($this->read($name, $payload)->entries as $entry) {
                if ($entry->aaguid !== null && $entry->metadataStatement !== null) {
                    $entries[strtolower($entry->aaguid)] = $entry;
                }
            }
        }

        return $this->entries = $entries;
    }

    private function read(string $name, string $payload): MetadataBLOBPayload
    {
        try {
            return $this->serializer->deserialize($payload, MetadataBLOBPayload::class, JsonEncoder::FORMAT);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('The metadata document "%s" is not a Metadata BLOB payload: %s', $name, $exception->getMessage()),
                previous: $exception,
            );
        }
    }
}
