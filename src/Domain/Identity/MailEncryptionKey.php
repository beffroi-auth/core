<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use function is_string;
use function sprintf;

/**
 * The certificate (RFC 8551) or the public key (RFC 9580) a person gave for the mail of the provider to be encrypted
 * to them, as the format that read it describes it: the format names itself by its kind.
 */
final readonly class MailEncryptionKey
{
    /**
     * @param list<EmailAddress> $addresses what the certificate or the key names, every one it names
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $kind,
        public string $material,
        public string $fingerprint,
        public array $addresses,
        public ?DateTimeImmutable $notBefore = null,
        public ?DateTimeImmutable $notAfter = null,
    ) {
        if ($kind === '') {
            throw new InvalidArgumentException('A key mail is encrypted to names its format.');
        }
        if ($material === '') {
            throw new InvalidArgumentException('A key mail is encrypted to holds the certificate or the key it was read from.');
        }
        if ($fingerprint === '') {
            throw new InvalidArgumentException('A key mail is encrypted to has a fingerprint.');
        }
        if ($notBefore !== null && $notAfter !== null && $notAfter < $notBefore) {
            throw new InvalidArgumentException('A key mail is encrypted to ends after it begins.');
        }
    }

    public function binds(EmailAddress $address): bool
    {
        return array_any($this->addresses, fn ($named) => $named->isTheSameMailboxAs($address));
    }

    public function isValidAt(DateTimeImmutable $moment): bool
    {
        return ($this->notBefore === null || $this->notBefore <= $moment) && ($this->notAfter === null || $moment < $this->notAfter);
    }

    /**
     * What the history of the person keeps of it: its format and its fingerprint, never the material.
     */
    public function described(): string
    {
        return sprintf('%s %s', $this->kind, $this->fingerprint);
    }

    /**
     * @return array{kind: string, material: string, fingerprint: string, addresses: list<string>, not_before: int|null, not_after: int|null}
     */
    public function toDocument(): array
    {
        $addresses = [];
        foreach ($this->addresses as $address) {
            $addresses[] = $address->value;
        }

        return [
            'kind' => $this->kind,
            'material' => $this->material,
            'fingerprint' => $this->fingerprint,
            'addresses' => $addresses,
            'not_before' => $this->notBefore?->getTimestamp(),
            'not_after' => $this->notAfter?->getTimestamp(),
        ];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException
     */
    public static function fromDocument(array $document): self
    {
        $read = new Document($document);
        $addresses = [];
        foreach ($read->entries('addresses') as $address) {
            if (! is_string($address)) {
                throw new InvalidArgumentException('The addresses a key mail is encrypted to names are strings.');
            }
            $addresses[] = new EmailAddress($address);
        }
        $notBefore = $read->nullableInt('not_before');
        $notAfter = $read->nullableInt('not_after');

        return new self(
            $read->nullableString('kind') ?? '',
            $read->nullableString('material') ?? '',
            $read->nullableString('fingerprint') ?? '',
            $addresses,
            $notBefore === null ? null : new DateTimeImmutable('@' . $notBefore),
            $notAfter === null ? null : new DateTimeImmutable('@' . $notAfter),
        );
    }
}
