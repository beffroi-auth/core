<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Application\Credential\EnrolmentRefusal;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Event\BackupCodesRenewed;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Another set of backup codes for the second factor a person already holds (architecture §3.1): the ones
 * they had stop working at that instant, spent or not, which is what asking for new ones means.
 *
 * **It is not an enrolment.** The authenticator and what it proves are untouched, so nothing is scanned
 * again and no code is asked for: what a person who lost their list needs is a list, not a ceremony.
 *
 * **None is drawn for a person of a federated domain** (decision 0029): a TOTP they held before their
 * domain was federated proves nothing at a login, and new codes would be ways in posed for them.
 *
 * **The codes are given back in the clear once, to the caller who asked**, as the enrolment gives them,
 * and what is stored is the hash of each one, as a password is.
 */
final readonly class BackupCodesRenewal
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private BackupCodeSet $codes,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private EnrolmentRefusal $enrolments,
    ) {
    }

    /**
     * @return list<string> the codes as they are spelled to the person, shown this once
     *
     * @throws CredentialEnrolmentFailedException when the person is of a federated domain (decision 0029)
     * @throws InvalidArgumentException when the credential does not take the set that was drawn
     * @throws LogicException when the credential is revoked: it proves nothing to renew codes for
     */
    public function renew(TotpCredential $totp, RequestContext $context): array
    {
        // new codes are new ways in, posed for nobody of a federated domain, whatever they held before
        $this->enrolments->assertTheirDomainAdmits($totp->user, 'set of backup codes');
        $spelled = $this->codes->draw();
        $totp->renewBackupCodes($this->codes->hashed($spelled));
        $this->credentials->save($totp);
        $this->events->dispatch(new BackupCodesRenewed($totp, $this->clock->now(), $context));

        return $spelled;
    }
}
