<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;

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
