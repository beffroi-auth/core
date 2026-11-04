<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use DateTimeImmutable;
use Exception;
use const FILTER_VALIDATE_IP;
use function filter_var;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_substr;
use function sprintf;
use function trim;

final readonly class RequestContext
{
    public const int USER_AGENT_MAX_LENGTH = 1024;

    /**
     * To the second, with no offset: read back in the default timezone, as the Doctrine datetime type does.
     */
    private const string RECEIVED_AT = 'Y-m-d H:i:s';

    public ?string $userAgent;

    public function __construct(
        public DateTimeImmutable $receivedAt,
        public ?string $ipAddress = null,
        ?string $userAgent = null,
        public ?GeoLocation $location = null,
        public ?Device $device = null,
    ) {
        if ($ipAddress !== null && filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(sprintf('"%s" is not an IP address.', $ipAddress));
        }
        if ($userAgent !== null) {
            $userAgent = trim($userAgent);
            $userAgent = $userAgent === '' ? null : mb_substr($userAgent, 0, self::USER_AGENT_MAX_LENGTH);
        }
        $this->userAgent = $userAgent;
    }

    public static function unknown(DateTimeImmutable $receivedAt): self
    {
        return new self($receivedAt);
    }

    public function withLocation(?GeoLocation $location): self
    {
        return new self($this->receivedAt, $this->ipAddress, $this->userAgent, $location, $this->device);
    }

    public function withDevice(?Device $device): self
    {
        return new self($this->receivedAt, $this->ipAddress, $this->userAgent, $this->location, $device);
    }

    public function isEnriched(): bool
    {
        return $this->location !== null || $this->device !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDocument(): array
    {
        return [
            'received_at' => $this->receivedAt->format(self::RECEIVED_AT),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'location' => $this->location?->toDocument(),
            'device' => $this->device?->toDocument(),
        ];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException
     */
    public static function fromDocument(array $document): self
    {
        $members = new Document($document);
        $written = $members->nullableString('received_at');
        if ($written === null) {
            throw new InvalidArgumentException('A request context says when the request was received.');
        }

        try {
            $receivedAt = new DateTimeImmutable($written);
        } catch (Exception $exception) {
            throw new InvalidArgumentException(sprintf('"%s" is not when a request was received.', $written), previous: $exception);
        }
        $location = $members->nested('location');
        $device = $members->nested('device');

        return new self(
            $receivedAt,
            $members->nullableString('ip_address'),
            $members->nullableString('user_agent'),
            $location === null ? null : GeoLocation::fromDocument($location),
            $device === null ? null : Device::fromDocument($device),
        );
    }
}
