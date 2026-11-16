<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use Beffroi\Core\Domain\Credential\Attestation\PasskeyAttestationRefusal;
use RuntimeException;
use Throwable;

/**
 * The attestation holds and the policy of the tenant refuses what it proves. The message is for the audit trail.
 */
final class PasskeyAttestationRefusedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly PasskeyAttestationRefusal $refusal,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
