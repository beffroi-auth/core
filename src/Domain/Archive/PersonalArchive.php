<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Archive;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * RGPD articles 15 and 20
 */
final class PersonalArchive implements ClassifiedFieldsInterface
{
    public const int KEPT_FOR_DAYS = 7;

    /**
     * The lane gives up on a message within a minute, so a build still empty after this has failed.
     */
    public const int BUILT_WITHIN_MINUTES = 15;

    public const string MEDIA_TYPE = 'application/json';

    public const string EXTENSION = 'json';

    public readonly Tenant $tenant;

    public private(set) ?string $content = null;

    public private(set) ?DateTimeImmutable $builtAt = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly DateTimeImmutable $askedAt,
        public readonly DateTimeImmutable $expiresAt,
    ) {
        if ($expiresAt <= $askedAt) {
            throw new InvalidArgumentException('An archive expires after it was asked for.');
        }
        $this->tenant = $user->tenant;
    }

    public static function asked(Uuid $id, User $user, DateTimeImmutable $now): self
    {
        return new self($id, $user, $now, $now->modify(sprintf('+%d days', self::KEPT_FOR_DAYS)));
    }

    /**
     * @throws InvalidArgumentException when it has already been built
     */
    public function fill(string $content, DateTimeImmutable $now): void
    {
        if ($this->content !== null) {
            throw new InvalidArgumentException('An archive is built once.');
        }
        if ($content === '') {
            throw new InvalidArgumentException('An archive holds a document.');
        }
        $this->content = $content;
        $this->builtAt = $now;
    }

    public function isReady(): bool
    {
        return $this->content !== null;
    }

    public function hasExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function hasStalled(DateTimeImmutable $now): bool
    {
        return ! $this->isReady() && $now >= $this->askedAt->modify(sprintf('+%d minutes', self::BUILT_WITHIN_MINUTES));
    }

    /**
     * Names the archive, never the person: a file name travels in a header that proxies and browsers log.
     */
    public function fileName(string $extension = self::EXTENSION): string
    {
        return sprintf('archive-%s.%s', $this->id->toRfc4122(), $extension);
    }

    /**
     * Sealed with no exemption: the archive holds in clear what every other family seals.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'askedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'expiresAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'builtAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'content' => new Sealed('personal_archive'),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
