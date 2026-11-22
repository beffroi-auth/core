<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use Beffroi\Core\Domain\Identity\MailEncryptionRefusal;
use RuntimeException;

/**
 * The message is for the audit trail, and never carries the certificate, the key or the address.
 */
final class MailEncryptionRefusedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly MailEncryptionRefusal $refusal,
        string $message,
    ) {
        parent::__construct($message);
    }
}
