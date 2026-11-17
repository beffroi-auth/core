<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Application\Identity\UserAlreadyExistsException;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;

/**
 * What an administrator of the tenant says of a person (decision 0038): a claim of OIDC Core §5.1, or the username of
 * the account.
 */
interface MemberInterface
{
    /**
     * The name of the member in the document of a person the administration API writes.
     */
    public function name(): string;

    /**
     * The claim an upstream source holds when it holds this member: the next sign-in through it would write it back.
     */
    public function heldAs(): string;

    /**
     * @return array<string, string|null> what it says now, each value under the name the history of the person writes it
     */
    public function of(User $user): array;

    /**
     * Saying what the member already says changes nothing.
     *
     * @throws InvalidArgumentException  when the value is not one the member holds
     * @throws UserAlreadyExistsException when another person of the tenant has the username said
     */
    public function say(User $user, mixed $value, DateTimeImmutable $now): void;
}
