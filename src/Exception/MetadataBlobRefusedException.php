<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * A Metadata BLOB (FIDO Metadata Service v3 §3.1) is not what it claims: not one signature, no
 * certificate chain, a signature that does not hold, a chain that does not lead to the pinned root, or a
 * payload that is not a blob. Nothing of it is kept.
 */
final class MetadataBlobRefusedException extends RuntimeException implements ExceptionInterface
{
}
