<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Domain\Identity\Claim\ClaimInterface;
use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * A claim a person may say of themselves, which an administrator says the same way, its field refusing the same values.
 */
final readonly class ClaimMember implements MemberInterface
{
    public function __construct(
        private ClaimInterface $claim,
    ) {
    }

    public function name(): string
    {
        return $this->claim->name();
    }

    public function heldAs(): string
    {
        return $this->claim->name();
    }

    public function of(User $user): array
    {
        return [
            $this->claim->name() => $this->claim->of($user),
        ];
    }

    public function say(User $user, mixed $value, DateTimeImmutable $now): void
    {
        $value = Said::text($value, $this->claim->name());
        if ($value === $this->claim->of($user)) {
            return;
        }
        $this->claim->field()->assertHolds($value);
        $this->claim->describe($user, $value, $now);
    }
}
