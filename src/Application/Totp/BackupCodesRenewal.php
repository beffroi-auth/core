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
     * @return list<string>
     *
     * @throws CredentialEnrolmentFailedException when the person is of a federated domain
     * @throws InvalidArgumentException when the credential does not take the set that was drawn
     * @throws LogicException when the credential is revoked
     */
    public function renew(TotpCredential $totp, RequestContext $context): array
    {
        // Refused to a federated domain even for a TOTP held before it: new codes are new ways in.
        $this->enrolments->assertTheirDomainAdmits($totp->user, 'set of backup codes');
        $spelled = $this->codes->draw();
        $totp->renewBackupCodes($this->codes->hashed($spelled));
        $this->credentials->save($totp);
        $this->events->dispatch(new BackupCodesRenewed($totp, $this->clock->now(), $context));

        return $spelled;
    }
}
