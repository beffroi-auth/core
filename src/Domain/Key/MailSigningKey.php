<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use function preg_match;

/**
 * A key the tenant signs its mail with, in one format (RFC 8551, RFC 3156): its private half sealed by the KMS as every
 * key of the tenant is, its public half and, for S/MIME, its certificate in clear. The current key of a format signs; a
 * key it replaced signs nothing, and stays served for the mail it signed until an administrator removes it.
 */
final class MailSigningKey
{
    /**
     * @var list<EmailAddress>
     */
    public readonly array $addresses;

    /**
     * @param string             $published what anyone may read of it: the PEM of its certificate and of the chain
     *                                      above it, or the ASCII armor of its OpenPGP public key
     * @param list<EmailAddress> $addresses the sender addresses it was set for
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $kind,
        public readonly string $fingerprint,
        public readonly string $published,
        public readonly string $sealedPrivateJwk,
        array $addresses,
        public readonly DateTimeImmutable $setAt,
        public readonly ?DateTimeImmutable $notAfter = null,
        public private(set) ?DateTimeImmutable $replacedAt = null,
    ) {
        if (preg_match('/^[a-z]+$/', $kind) !== 1) {
            throw new InvalidArgumentException('A mail signing key names its format in lowercase letters.');
        }
        if (preg_match('/^[0-9A-F]{40,64}$/', $fingerprint) !== 1) {
            throw new InvalidArgumentException('The fingerprint of a mail signing key is 40 to 64 uppercase hexadecimal digits.');
        }
        if ($published === '' || $sealedPrivateJwk === '') {
            throw new InvalidArgumentException('A mail signing key holds its public half and its sealed private half.');
        }
        if ($addresses === []) {
            throw new InvalidArgumentException('A mail signing key names the sender address it signs for.');
        }
        $this->addresses = $addresses;
    }

    public function isCurrent(): bool
    {
        return $this->replacedAt === null;
    }

    public function replace(DateTimeImmutable $now): void
    {
        $this->replacedAt ??= $now;
    }

    /**
     * Whether it may sign a mail written from that address at that moment: a key replaced, out of its dates or set for
     * another sender signs nothing.
     */
    public function signsFor(EmailAddress $sender, DateTimeImmutable $moment): bool
    {
        if (! $this->isCurrent() || $this->notAfter !== null && $moment >= $this->notAfter) {
            return false;
        }
        return array_any($this->addresses, fn ($address) => $address->isTheSameMailboxAs($sender));
    }
}
