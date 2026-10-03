<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * One thing a person may be able to say about themselves (issue #452): a claim of OIDC Core §5.1, the
 * name it is known by, how it is read of a person and how that person's saying it is written.
 *
 * A class per claim and not a name in a list: what the pages offer and what a tenant may open are derived
 * from what is wired, so a claim this provider comes to hold is a class added and nothing edited. A claim
 * that is not written the way the others are says so by being its own class, which is what the phone
 * number is.
 *
 * The value is a string or nothing, "nothing" being a person taking back what they had said. What a value
 * has to look like is the model's and not this interface's: a birthdate that is not a date is refused by
 * the profile that holds it, wherever the value came from.
 */
interface ClaimInterface
{
    /**
     * The name of the claim (OIDC Core §5.1), which the policy of a tenant names and a form field carries.
     */
    public function name(): string;

    /**
     * What this provider holds of that person under this claim, null when it holds nothing.
     */
    public function of(User $user): ?string;

    /**
     * The person once they have said this of themselves; null takes back what they had said.
     *
     * @throws InvalidArgumentException when the value is not one this claim holds
     */
    public function describe(User $user, ?string $value, DateTimeImmutable $now): void;
}
