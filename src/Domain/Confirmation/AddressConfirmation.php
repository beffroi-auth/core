<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Confirmation;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * NIST SP 800-63B-4 §4.2.1.2, decision 0026
 *
 * What establishes one address of a person once, by the code the person types or by the link they follow, each kept
 * as a hash of itself bound to the address it was sent to: the row holds nothing of the address, and a code
 * establishes that address and no other.
 */
abstract class AddressConfirmation implements ClassifiedFieldsInterface
{
    /**
     * NIST SP 800-63B-4 §3.2.2 bounds consecutive failures to a hundred at most.
     */
    public const int FAILURES = 5;

    public readonly Tenant $tenant;

    public private(set) int $failures = 0;

    public private(set) ?DateTimeImmutable $spentAt = null;

    public function __construct(
        /**
         * Not readonly: a mapper building lazy references assigns it twice.
         */
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly PasswordHash $code,
        public readonly PasswordHash $link,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $expiresAt,
    ) {
        if ($expiresAt <= $createdAt) {
            throw new InvalidArgumentException('A confirmation expires after it was sent.');
        }
        $this->tenant = $user->tenant;
    }

    /**
     * What is hashed of a code or of a link: the secret, of a fixed length, then the address it was sent to.
     */
    public static function bound(#[SensitiveParameter] string $secret, string $address): string
    {
        return $secret . "\n" . $address;
    }

    public function isFor(User $user): bool
    {
        return $this->user->id->equals($user->id);
    }

    public function isSpent(): bool
    {
        return $this->spentAt !== null;
    }

    public function opens(DateTimeImmutable $now): bool
    {
        return ! $this->isSpent() && $now < $this->expiresAt && $this->failures < self::FAILURES && $this->user->isEnabled();
    }

    public function failed(): void
    {
        ++$this->failures;
    }

    /**
     * @throws InvalidArgumentException when it was spent already: a second spending would establish the address twice
     */
    public function spend(DateTimeImmutable $now): void
    {
        if ($this->isSpent()) {
            throw new InvalidArgumentException('This confirmation is spent already.');
        }
        $this->spentAt = $now;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'code' => new NoPersonalDatum(Reason::HASHED),
            'link' => new NoPersonalDatum(Reason::HASHED),
            'failures' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'expiresAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'spentAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
