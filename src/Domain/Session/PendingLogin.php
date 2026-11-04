<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * A login whose first factor was verified, waiting for the second: it opens no session and grants nothing.
 */
final readonly class PendingLogin
{
    /**
     * Ten times what a step of an authenticator lasts.
     */
    public const string LIFETIME = 'PT5M';

    /**
     * @param non-empty-list<AuthenticationMethod> $methods
     */
    public function __construct(
        public Uuid $userId,
        public array $methods,
        public DateTimeImmutable $provenAt,
        public DateTimeImmutable $expiresAt,
    ) {
        if ($methods === []) {
            throw new InvalidArgumentException('A pending login holds what its first step proved.');
        }
        if ($expiresAt <= $provenAt) {
            throw new InvalidArgumentException('A pending login expires after its first step.');
        }
    }

    /**
     * @param non-empty-list<AuthenticationMethod> $methods
     */
    public static function provenAt(Uuid $userId, array $methods, DateTimeImmutable $now): self
    {
        return new self($userId, $methods, $now, $now->add(new DateInterval(self::LIFETIME)));
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }
}
