<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Identity;

use DateTimeImmutable;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use function sprintf;
use function strlen;
use Symfony\Component\Uid\Uuid;
use function trim;

/**
 * An identity of a tenant; credentials, linked identities and group memberships hang off it (decision 0002).
 */
final class User
{
    public private(set) ?DateTimeImmutable $disabledAt = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public readonly Subject $subject,
        public private(set) string $username,
        public private(set) ?EmailAddress $email,
        public private(set) bool $emailVerified,
        public private(set) Profile $profile,
        public private(set) ?string $phoneNumber,
        public private(set) bool $phoneNumberVerified,
        public private(set) ?Address $address,
        public readonly DateTimeImmutable $createdAt,
        public private(set) DateTimeImmutable $updatedAt,
    ) {
        $this->username = self::normalizeUsername($username);
        self::assertVerifiable($email, $emailVerified, 'email address');
        self::assertVerifiable($phoneNumber, $phoneNumberVerified, 'phone number');
    }

    public static function create(
        Uuid $id,
        Tenant $tenant,
        Subject $subject,
        string $username,
        DateTimeImmutable $now,
        ?EmailAddress $email = null,
        bool $emailVerified = false,
    ): self {
        return new self($id, $tenant, $subject, $username, $email, $emailVerified, Profile::empty(), null, false, null, $now, $now);
    }

    public function rename(string $username, DateTimeImmutable $now): void
    {
        $this->username = self::normalizeUsername($username);
        $this->updatedAt = $now;
    }

    /**
     * Verification never carries over to a new address.
     */
    public function changeEmail(?EmailAddress $email, bool $verified, DateTimeImmutable $now): void
    {
        self::assertVerifiable($email, $verified, 'email address');
        $this->email = $email;
        $this->emailVerified = $verified;
        $this->updatedAt = $now;
    }

    public function changePhoneNumber(?string $phoneNumber, bool $verified, DateTimeImmutable $now): void
    {
        self::assertVerifiable($phoneNumber, $verified, 'phone number');
        $this->phoneNumber = $phoneNumber;
        $this->phoneNumberVerified = $verified;
        $this->updatedAt = $now;
    }

    public function updateProfile(Profile $profile, DateTimeImmutable $now): void
    {
        $this->profile = $profile;
        $this->updatedAt = $now;
    }

    public function changeAddress(?Address $address, DateTimeImmutable $now): void
    {
        $this->address = $address?->isEmpty() === true ? null : $address;
        $this->updatedAt = $now;
    }

    public function disable(DateTimeImmutable $now): void
    {
        if ($this->disabledAt !== null) {
            return;
        }
        $this->disabledAt = $now;
        $this->updatedAt = $now;
    }

    public function enable(DateTimeImmutable $now): void
    {
        if ($this->disabledAt === null) {
            return;
        }
        $this->disabledAt = null;
        $this->updatedAt = $now;
    }

    public function isEnabled(): bool
    {
        return $this->disabledAt === null;
    }

    public function belongsTo(Tenant $tenant): bool
    {
        return $this->tenant->equals($tenant);
    }

    /**
     * A contact detail nobody gave cannot have been verified; the same rule when the user is created and
     * when it changes, for the address as for the number.
     *
     * @throws InvalidArgumentException
     */
    private static function assertVerifiable(EmailAddress|string|null $value, bool $verified, string $what): void
    {
        if ($value === null && $verified) {
            throw new InvalidArgumentException(sprintf('An absent %s cannot be verified.', $what));
        }
    }

    private static function normalizeUsername(string $username): string
    {
        $username = trim($username);
        if ($username === '' || strlen($username) > 255) {
            throw new InvalidArgumentException('A username is 1 to 255 characters long.');
        }

        return $username;
    }
}
