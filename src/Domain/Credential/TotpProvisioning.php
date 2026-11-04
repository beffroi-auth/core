<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use function count;
use Beffroi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;
use function sprintf;

final readonly class TotpProvisioning
{
    /**
     * @param list<string> $backupCodes as many as a credential holds
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
