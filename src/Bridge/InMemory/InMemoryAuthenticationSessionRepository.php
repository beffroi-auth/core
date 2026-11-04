<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use function array_values;
use function array_filter;
use Beffroi\Core\Domain\Listing\Period;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Beffroi\Core\Port\CanFindUnendedSessionsInterface;
use Beffroi\Core\Port\CanPageActiveSessionsInterface;
use Beffroi\Core\Port\SubjectsOfSessionsInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

final class InMemoryAuthenticationSessionRepository implements
    AuthenticationSessionRepositoryInterface,
    CanFindUnendedSessionsInterface,
    CanPageActiveSessionsInterface,
    SubjectsOfSessionsInterface
{
    /**
     * @var array<string, AuthenticationSession>
     */
    private array $sessions = [];

    /**
     * @var array<string, Subject> by session id
     */
    private array $subjects = [];

    public private(set) int $saves = 0;

    public function find(Uuid $id): ?AuthenticationSession
    {
        return $this->sessions[$id->toRfc4122()] ?? null;
    }

    public function of(Uuid $session): Subject
    {
        return $this->subjects[$session->toRfc4122()] ?? throw new RuntimeException(sprintf('No session "%s" was saved.', $session->toRfc4122()));
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
        return array_values(array_filter(
            $this->findUnendedByUser($user),
            static fn (AuthenticationSession $session): bool => $session->isActive($now),
        ));
    }

    public function findUnendedByUser(User $user): array
    {
        $unended = [];
        foreach ($this->sessions as $session) {
            if ($session->user->id->equals($user->id) && ! $session->hasEnded()) {
                $unended[] = $session;
            }
        }

        return $unended;
    }

    public function save(AuthenticationSession $session): void
    {
        ++$this->saves;
        $this->sessions[$session->id->toRfc4122()] = $session;
        $this->subjects[$session->id->toRfc4122()] = $session->user->subject;
    }
}
