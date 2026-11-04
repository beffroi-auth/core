<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\PersonalData\BlindIndex;
use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class LinkedIdentity implements ClassifiedFieldsInterface
{
    public readonly Tenant $tenant;

    /**
     * Blind indexes of the provider and of the subject, filled by the storage on every write: a sealed column
     * can be neither unique nor looked up.
     */
    public private(set) string $providerIndex = '';

    public private(set) string $subjectIndex = '';

    /**
     * @param array<string, mixed> $claims
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly string $provider,
        public readonly string $subject,
        public private(set) array $claims,
        public readonly DateTimeImmutable $linkedAt,
        public private(set) DateTimeImmutable $lastSeenAt,
    ) {
        if ($provider === '') {
            throw new InvalidArgumentException('A linked identity names its provider.');
        }
        if ($subject === '') {
            throw new InvalidArgumentException('A linked identity carries the subject the provider gave.');
        }
        $this->tenant = $user->tenant;
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function refresh(array $claims, DateTimeImmutable $now): void
    {
        $this->claims = $claims;
        $this->lastSeenAt = $now;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'provider' => new Sealed('linked_identity'),
            'providerIndex' => new BlindIndex(),
            'subject' => new Sealed('linked_identity'),
            'subjectIndex' => new BlindIndex(),
            'claims' => new Sealed('linked_identity'),
            'linkedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'lastSeenAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
