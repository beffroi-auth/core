<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Application\Identity\UserAlreadyExistsException;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\UserRepositoryInterface;
use DateTimeImmutable;

/**
 * The name a person signs in with, which no other person of the tenant has. An upstream provider names its people by
 * their email address, so it holds their username with their address.
 */
final readonly class UsernameMember implements MemberInterface
{
    public const string NAME = 'username';

    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function heldAs(): string
    {
        return EmailMember::NAME;
    }

    public function of(User $user): array
    {
        return [
            self::NAME => $user->username,
        ];
    }

    public function say(User $user, mixed $value, DateTimeImmutable $now): void
    {
        $value = Said::text($value, self::NAME);
        if ($value === null) {
            throw new InvalidArgumentException('A person keeps a username.');
        }
        if ($value === $user->username) {
            return;
        }
        $holder = $this->users->findByUsername($user->tenant, $value);
        if ($holder !== null && ! $holder->id->equals($user->id)) {
            throw UserAlreadyExistsException::username($value);
        }
        $user->rename($value, $now);
    }
}
