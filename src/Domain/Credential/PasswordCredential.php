<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Symfony\Component\Uid\Uuid;

final class PasswordCredential extends Credential implements ProvingCredentialInterface
{
    /**
     * The lists are published in batches: asking more often finds nothing more.
     */
    public const string LOOKUP_AGAIN_AFTER = 'P30D';

    public private(set) DateTimeImmutable $hashChangedAt;

    public private(set) ?DateTimeImmutable $breachCheckedAt = null;

    public private(set) ?DateTimeImmutable $breachFoundAt = null;

    public function __construct(
        Uuid $id,
        User $user,
        public private(set) PasswordHash $hash,
        DateTimeImmutable $createdAt,
    ) {
        parent::__construct($id, $user, $createdAt);
        $this->hashChangedAt = $createdAt;
    }

    public function kind(): string
    {
        return 'password';
    }

    public static function method(): AuthenticationMethod
    {
        return AuthenticationMethod::password();
    }

    public function provableMethods(): array
    {
        return [self::method()];
    }

    public function recordUse(DateTimeImmutable $now): void
    {
        $this->markUsed($now);
    }

    public function rehash(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
    }

    public function changePassword(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
        $this->breachCheckedAt = null;
        $this->breachFoundAt = null;
    }

    public function notFoundInABreach(DateTimeImmutable $now): void
    {
        $this->breachCheckedAt = $now;
        $this->breachFoundAt = null;
    }

    public function foundInABreach(DateTimeImmutable $now): void
    {
        $this->breachCheckedAt = $now;
        $this->breachFoundAt = $now;
    }

    public function isBreached(): bool
    {
        return $this->breachFoundAt !== null;
    }

    public function dueForABreachLookup(DateTimeImmutable $now): bool
    {
        if ($this->breachCheckedAt === null) {
            return true;
        }

        return $this->breachCheckedAt->add(new DateInterval(self::LOOKUP_AGAIN_AFTER)) <= $now;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            ...parent::classifiedFields(),
            'hash' => new NoPersonalDatum(Reason::HASHED . ', with the algorithm it was computed with and the parameters it was computed under'),
            'hashChangedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'breachCheckedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'breachFoundAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
        ];
    }
}
