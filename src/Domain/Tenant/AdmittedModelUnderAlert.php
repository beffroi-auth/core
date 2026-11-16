<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use DateTimeZone;
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * A model under a security alert the tenant still enrols, up to and including a day of UTC (decision 0006: an exception is
 * named and bounded).
 */
final readonly class AdmittedModelUnderAlert
{
    private const string DAY = 'Y-m-d';

    public function __construct(
        public Uuid $aaguid,
        public DateTimeImmutable $until,
    ) {
    }

    /**
     * A plain pattern for the AAGUID: it is any 128 bits, whatever the variant of RFC 9562 says (WebAuthn Level 3 §6.1).
     *
     * @throws InvalidArgumentException
     */
    public static function fromStrings(string $aaguid, string $until): self
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di', $aaguid) !== 1) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is no AAGUID: a model under a security alert is named by the AAGUID of its authenticator.',
                $aaguid,
            ));
        }
        $day = DateTimeImmutable::createFromFormat('!' . self::DAY, $until, new DateTimeZone('UTC'));
        if ($day === false || $day->format(self::DAY) !== $until) {
            throw new InvalidArgumentException(sprintf(
                'The model "%s" is admitted under its security alert until a day written as "2027-03-31", not "%s".',
                $aaguid,
                $until,
            ));
        }

        return new self(Uuid::fromString($aaguid), $day);
    }

    public function day(): string
    {
        return $this->until->format(self::DAY);
    }

    public function admits(Uuid $aaguid, DateTimeImmutable $now): bool
    {
        return $this->aaguid->equals($aaguid) && $now->setTimezone(new DateTimeZone('UTC'))->format(self::DAY) <= $this->day();
    }

    public function equals(self $other): bool
    {
        return $this->aaguid->equals($other->aaguid) && $this->day() === $other->day();
    }
}
