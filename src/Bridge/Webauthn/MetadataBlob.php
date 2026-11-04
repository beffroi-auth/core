<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Webauthn\MetadataService\Service\MetadataBLOBPayload;

/**
 * A Metadata BLOB whose signature and chain held (FIDO Metadata Service v3.1.1 §3.1).
 */
final readonly class MetadataBlob
{
    public function __construct(
        public string $payload,
        public MetadataBLOBPayload $content,
    ) {
    }
}
