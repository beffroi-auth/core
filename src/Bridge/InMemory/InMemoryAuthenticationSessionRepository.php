<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use function array_values;
use function array_filter;
use Beffroi\Core\Domain\Listing\Period;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Beffroi\Core\Port\CanPageActiveSessionsInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryAuthenticationSessionRepository implements AuthenticationSessionRepositoryInterface, CanPageActiveSessionsInterface
{
    /**
     * @var array<string, AuthenticationSession>
     */
    private array $sessions = [];

    /**
     * For tests that must see a write happen on an object they already hold.
     */
    public private(set) int $saves = 0;

    public function find(Uuid $id): ?AuthenticationSession
    {
        return $this->sessions[$id->toRfc4122()] ?? null;
    }

    /**
     * @return Listing<AuthenticationSession>
     */
    public function pageOfActiveSessions(User $user, DateTimeImmutable $now, Pagination $pagination, ?Period $period = null): Listing
    {
        return Listing::of(
            array_values(array_filter(
                $this->findActiveByUser($user, $now),
                static fn (AuthenticationSession $session): bool => $period?->holds($session->establishedAt) !== false,
            )),
            $pagination,
        );
    }

    public function findActiveByUser(User $user, DateTimeImmutable $now): array
    {
        $active = [];
        foreach ($this->sessions as $session) {
            if ($session->user->id->equals($user->id) && $session->isActive($now)) {
                $active[] = $session;
            }
        }

        return $active;
    }

    public function save(AuthenticationSession $session): void
    {
        ++$this->saves;
        $this->sessions[$session->id->toRfc4122()] = $session;
    }
}
