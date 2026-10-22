<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * The language a person told this provider they read, written on their account (issue #471).
 *
 * It is the one claim of a profile this provider needs in order to write to a person at all
 * (architecture §5.7): what reaches them out of any request of theirs is written in the language the
 * claim names, so a person who says on a page that they read French is written to in French afterwards.
 *
 * It replaces that claim and nothing else. Saying the whole profile is what provisioning does, and it
 * clears what it is not told (UserProvisioner); here a person named one thing about themselves.
 *
 * **Nothing is written when the source of the person holds that claim** (decision 0002): the provider
 * that created them writes it, and a client would read the language chosen on a page until their next
 * sign-in through it. The choice is not lost for all that: the cookie of the browser carries it, which is
 * what the next journey of that browser starts in, and what this provider writes to that person in is the
 * language their provider says they read.
 */
final readonly class LanguageRecording
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ClockInterface $clock,
    ) {
    }

    public function record(User $user, string $locale): void
    {
        if ($user->source->holds('locale')) {
            return;
        }
        $user->updateProfile($user->profile->readIn($locale), $this->clock->now());
        $this->users->save($user);
    }
}
