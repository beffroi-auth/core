<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use function array_is_list;
use function array_key_exists;
use Beffroi\Core\Domain\Identity\Address;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use function is_array;
use function sprintf;

/**
 * OIDC Core §5.1.1. An object is merged into the address member by member, as a merge patch merges it (RFC 7396 §2): a
 * member it names is written, null taking it away, and a member it does not name is kept. Null takes the whole address
 * away.
 */
final readonly class AddressMember implements MemberInterface
{
    public const string NAME = 'address';

    public function name(): string
    {
        return self::NAME;
    }

    public function heldAs(): string
    {
        return self::NAME;
    }

    public function of(User $user): array
    {
        $written = [];
        foreach (($user->address ?? new Address())->toDocument() as $member => $value) {
            $written[self::NAME . '.' . $member] = $value;
        }

        return $written;
    }

    public function say(User $user, mixed $value, DateTimeImmutable $now): void
    {
        if ($value === null) {
            if ($user->address !== null) {
                $user->changeAddress(null, $now);
            }

            return;
        }
        if (! is_array($value) || $value !== [] && array_is_list($value)) {
            throw new InvalidArgumentException('"address" is an object of the members of OIDC Core §5.1.1, or null to take it away.');
        }
        $document = ($user->address ?? new Address())->toDocument();
        foreach ($value as $member => $said) {
            if (! array_key_exists($member, $document)) {
                throw new InvalidArgumentException(sprintf('An address has no member "%s" (OIDC Core §5.1.1).', $member));
            }
            $document[$member] = Said::text($said, self::NAME . '.' . $member);
        }
        $address = Address::fromDocument($document);
        $address = $address->isEmpty() ? null : $address;
        if ($address?->toDocument() !== $user->address?->toDocument()) {
            $user->changeAddress($address, $now);
        }
    }
}
