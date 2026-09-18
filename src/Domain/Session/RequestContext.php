<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use DateTimeImmutable;
use const FILTER_VALIDATE_IP;
use function filter_var;
use Heidi\Core\Exception\InvalidArgumentException;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function trim;

/**
 * Where a request came from and with what, carried by every session and event (architecture §3.5).
 * Enrichments (location, device) are asynchronous.
 */
final readonly class RequestContext
{
    public const int USER_AGENT_MAX_LENGTH = 1024;

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

    public function userAgentLength(): int
    {
        return $this->userAgent === null ? 0 : mb_strlen($this->userAgent);
    }
}
