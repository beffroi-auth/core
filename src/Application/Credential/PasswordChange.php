<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Domain\Credential\CredentialChange;
use Beffroi\Core\Domain\Credential\PasswordChangeRefusal;
use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Domain\Event\PasswordChanged;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Exception\PasswordChangeRefusedException;
use Beffroi\Core\Exception\PasswordRefusedException;
use Beffroi\Core\Port\CredentialChangeNotifierInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;

/**
 * A wrong password in use is not counted against the login, or a stolen session could lock its owner out.
 * What happens to the other sessions is the caller's policy.
 */
final readonly class PasswordChange
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ActivePassword $activePassword,
        private PasswordHasherInterface $hasher,
        private BreachedPasswordRefusal $breaches,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private FederatedDomains $federated,
        private CredentialChangeNotifierInterface $notifier,
    ) {
    }

    /**
     * @throws PasswordChangeRefusedException
     * @throws PasswordRefusedException when a list of compromised passwords counts it or cannot be asked
     * @throws LogicException when no hasher of this deployment handles the algorithm the stored hash names
     */
    public function change(
        User $user,
        #[SensitiveParameter]
        string $inUse,
        #[SensitiveParameter]
        PlainPassword $posed,
        RequestContext $context,
    ): void {
        // Checked before the row is read: a password row left behind by a hardening counts as none.
        if (! $user->tenant->loginFloor->admits([PasswordCredential::method()])) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::NoPasswordHeld,
                'The tenant signs its users in without a password; there is nothing to replace.',
            );
        }
        if (! $this->federated->admitsLocalCredentials($user)) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::FederatedDomain,
                'The person is of an e-mail domain the tenant federates; no password of theirs is replaced.',
            );
        }
        $credential = $this->activePassword->of($user);
        if ($credential === null) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::NoPasswordHeld,
                'The user holds no password; there is nothing to replace.',
            );
        }
        if (! $this->hasher->verify($credential->hash, $inUse)) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::WrongPassword,
                'What was typed is not the password in use.',
            );
        }
        if ($this->hasher->verify($credential->hash, $posed->value)) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::SameAsBefore,
                'The password posed is the one already held.',
            );
        }
        // Last, being the one check that leaves this process: a refused change sends nothing out.
        $count = $this->breaches->count($posed);
        $now = $this->clock->now();
        $credential->changePassword($this->hasher->hash($posed->value), $now);
        if ($count->wasAsked()) {
            $credential->notFoundInABreach($now);
        }
        $this->credentials->save($credential);
        $this->events->dispatch(new PasswordChanged($credential, $now, $context));
        $this->notifier->tell(new CredentialChange($user, PasswordChanged::NAME, $now, $credential->kind()));
    }
}
