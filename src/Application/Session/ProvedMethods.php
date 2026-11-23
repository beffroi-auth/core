<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Session;

use function array_keys;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use DateTimeImmutable;
use function sort;

/**
 * The methods a list of sessions can be narrowed to (SessionQuestion::$provedWith).
 */
final readonly class ProvedMethods
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
    ) {
    }

    /**
     * RFC 8176 §2
     *
     * @return list<string> every method reference a session of the person still open proved, each once, in the order of their names
     */
    public function of(User $user, DateTimeImmutable $now): array
    {
        $methods = [];
        foreach ($this->sessions->findActiveByUser($user, $now) as $session) {
            foreach ($session->proofs->methodReferences() as $method) {
                $methods[$method] = true;
            }
        }
        $methods = array_keys($methods);
        sort($methods);

        return $methods;
    }
}
