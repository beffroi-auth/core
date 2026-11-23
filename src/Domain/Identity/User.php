<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Notification\ChosenChannels;
use Beffroi\Core\Domain\PersonalData\BlindIndex;
use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\MailEncryptionRefusedException;
use function sprintf;
use function strlen;
use Symfony\Component\Uid\Uuid;
use function trim;

final class User implements ClassifiedFieldsInterface
{
    public private(set) ?DateTimeImmutable $disabledAt = null;

    /**
     * A blind index the storage fills on every write: a sealed column can be neither unique nor looked up.
     */
    public private(set) string $usernameIndex = '';

    /**
     * Filled the same way, and not unique: two local persons of a tenant may share an address.
     */
    public private(set) ?string $emailIndex = null;

    /**
     * What the mail of the provider is encrypted to once the person asked for it; a new address takes it back as it
     * takes back the verification.
     */
    public private(set) ?MailEncryptionKey $mailEncryption = null;
    public private(set) ChosenChannels $notificationChannels;

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
        public private(set) UserSource $source,
    ) {
        $this->username = self::normalizeUsername($username);
        self::assertVerifiable($email, $emailVerified, 'email address');
        self::assertVerifiable($phoneNumber, $phoneNumberVerified, 'phone number');
        $this->notificationChannels = ChosenChannels::none();
    }

    public static function create(
        Uuid $id,
        Tenant $tenant,
        Subject $subject,
        string $username,
        DateTimeImmutable $now,
        ?EmailAddress $email = null,
        bool $emailVerified = false,
        ?UserSource $source = null,
    ): self {
        return new self(
            $id,
            $tenant,
            $subject,
            $username,
            $email,
            $emailVerified,
            Profile::empty(),
            null,
            false,
            null,
            $now,
            $now,
            $source ?? UserSource::local(),
        );
    }

    public function rename(string $username, DateTimeImmutable $now): void
    {
        $this->username = self::normalizeUsername($username);
        $this->updatedAt = $now;
    }

    /**
     * Verification never carries over to a new address, nor does the key mail to it was encrypted to.
     */
    public function changeEmail(?EmailAddress $email, bool $verified, DateTimeImmutable $now): void
    {
        self::assertVerifiable($email, $verified, 'email address');
        if ($email?->value !== $this->email?->value) {
            $this->mailEncryption = null;
        }
        $this->email = $email;
        $this->emailVerified = $verified;
        $this->updatedAt = $now;
    }

    /**
     * @throws InvalidArgumentException when an absent number is said verified, or the number is not plain text
     */
    public function changePhoneNumber(?string $phoneNumber, bool $verified, DateTimeImmutable $now): void
    {
        self::assertVerifiable($phoneNumber, $verified, 'phone number');
        if ($phoneNumber !== null && ! PlainText::holds($phoneNumber, PlainText::MAX_PHONE_NUMBER_LENGTH)) {
            throw new InvalidArgumentException(sprintf(
                'A phone number is plain text of at most %d characters, with no control, format or line separator character.',
                PlainText::MAX_PHONE_NUMBER_LENGTH,
            ));
        }
        $this->phoneNumber = $phoneNumber;
        $this->phoneNumberVerified = $verified;
        $this->updatedAt = $now;
    }

    public function verifiedEmail(): ?EmailAddress
    {
        return $this->emailVerified ? $this->email : null;
    }

    /**
     * @throws MailEncryptionRefusedException when no address is verified, the key names another one, or it is not
     *                                        valid at that moment
     */
    public function encryptMailTo(MailEncryptionKey $key, DateTimeImmutable $now): void
    {
        $verified = $this->verifiedEmail();
        if ($verified === null) {
            throw new MailEncryptionRefusedException(
                MailEncryptionRefusal::NoVerifiedAddress,
                'Mail is encrypted to a verified address, and this person has none.',
            );
        }
        if (! $key->binds($verified)) {
            throw new MailEncryptionRefusedException(
                MailEncryptionRefusal::AnotherAddress,
                'The certificate or the key does not name the verified address of this person.',
            );
        }
        if (! $key->isValidAt($now)) {
            throw new MailEncryptionRefusedException(MailEncryptionRefusal::NotValidNow, 'The certificate or the key is not valid at this moment.');
        }
        $this->mailEncryption = $key;
        $this->updatedAt = $now;
    }

    public function writeMailInClear(DateTimeImmutable $now): void
    {
        if ($this->mailEncryption === null) {
            return;
        }
        $this->mailEncryption = null;
        $this->updatedAt = $now;
    }

    public function verifiedPhoneNumber(): ?string
    {
        return $this->phoneNumberVerified ? $this->phoneNumber : null;
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

    /**
     * @throws InvalidArgumentException when the source names another provider than the one that created the person
     */
    public function heldBy(UserSource $source, DateTimeImmutable $now): void
    {
        if ($source->provider !== $this->source->provider) {
            throw new InvalidArgumentException('What a source holds of a person changes, and the provider that created the person does not.');
        }
        $this->source = $source;
        $this->updatedAt = $now;
    }

    public function chooseChannels(ChosenChannels $chosen, DateTimeImmutable $now): void
    {
        $this->notificationChannels = $chosen;
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

    public function maySayOfThemselves(string $claim): bool
    {
        return $this->tenant->selfServedClaims->opens($claim) && ! $this->source->holds($claim);
    }

    /**
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

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'subject' => new NoPersonalDatum(Reason::THE_SUBJECT),
            'username' => new Sealed('user'),
            'usernameIndex' => new BlindIndex(),
            'email' => new Sealed('user'),
            'emailIndex' => new BlindIndex(),
            'emailVerified' => new NoPersonalDatum('whether an address or a number was verified, which says nothing of either'),
            'profile' => new Sealed('user'),
            'phoneNumber' => new Sealed('user'),
            'phoneNumberVerified' => new NoPersonalDatum('whether an address or a number was verified, which says nothing of either'),
            'address' => new Sealed('user'),
            'source' => new Sealed('user'),
            'mailEncryption' => new Sealed('user'),
            'notificationChannels' => new Sealed('user'),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'updatedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'disabledAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
        ];
    }
}
