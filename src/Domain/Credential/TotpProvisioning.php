<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use function count;
use Heidi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;
use function sprintf;

/**
 * What a TOTP enrolment issues before any credential exists (the challenge of CredentialEnrolmentInterface):
 * the secret, the otpauth URI the authenticator scans (the Key Uri Format of Google Authenticator, over
 * RFC 6238), and the backup codes in clear, shown once. Nothing of it is stored: an enrolment abandoned
 * here leaves nothing behind, one completed keeps the secret sealed and the codes hashed.
 */
final readonly class TotpProvisioning
{
    /**
     * @param list<string> $backupCodes exactly the number a credential holds
     */
    public function __construct(
        public TotpSecret $secret,
        public string $uri,
        #[SensitiveParameter]
        public array $backupCodes,
    ) {
        if ($uri === '') {
            throw new InvalidArgumentException('A TOTP provisioning carries the URI the authenticator scans.');
        }
        if (count($backupCodes) !== TotpCredential::BACKUP_CODES) {
            throw new InvalidArgumentException(sprintf('A TOTP enrolment issues %d backup codes.', TotpCredential::BACKUP_CODES));
        }
    }
}
