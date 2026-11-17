<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Event\AuthenticationFailed;
use Beffroi\Core\Domain\Event\AuthenticationSessionEnded;
use Beffroi\Core\Domain\Event\BackupCodeUsed;
use Beffroi\Core\Domain\Event\BackupCodesRenewed;
use Beffroi\Core\Domain\Event\CredentialEnrolled;
use Beffroi\Core\Domain\Event\CredentialRenamed;
use Beffroi\Core\Domain\Event\CredentialForgotten;
use Beffroi\Core\Domain\Event\CredentialRevoked;
use Beffroi\Core\Domain\Event\EnrolmentTicketIssued;
use Beffroi\Core\Domain\Event\PasskeyUsed;
use Beffroi\Core\Domain\Event\PasswordChanged;
use Beffroi\Core\Domain\Event\PasswordTicketIssued;
use Beffroi\Core\Domain\Event\TotpTicketIssued;
use Beffroi\Core\Domain\Event\PasswordUsed;
use Beffroi\Core\Domain\Event\PersonAskedForTheirArchive;
use Beffroi\Core\Domain\Event\PersonDescribed;
use Beffroi\Core\Domain\Event\PersonDescribedThemselves;
use Beffroi\Core\Domain\Event\PersonDroppedTheirArchive;
use Beffroi\Core\Domain\Event\PersonCreated;
use Beffroi\Core\Domain\Event\PersonProvisioned;
use Beffroi\Core\Domain\Event\PersonTookTheirArchive;
use Beffroi\Core\Domain\Event\TotpUsed;
use Beffroi\Core\Domain\Event\UserAuthenticated;

final class ActsOfAnAccount
{
    /**
     * @var list<string>
     */
    public const array NAMES = [
        PersonCreated::NAME,
        PersonProvisioned::NAME,
        UserAuthenticated::NAME,
        AuthenticationFailed::NAME,
        PasswordUsed::NAME,
        PasskeyUsed::NAME,
        TotpUsed::NAME,
        BackupCodeUsed::NAME,
        AuthenticationSessionEnded::NAME,
        CredentialEnrolled::NAME,
        CredentialRevoked::NAME,
        CredentialForgotten::NAME,
        EnrolmentTicketIssued::NAME,
        PasswordTicketIssued::NAME,
        TotpTicketIssued::NAME,
        CredentialRenamed::NAME,
        PasswordChanged::NAME,
        BackupCodesRenewed::NAME,
        PersonDescribedThemselves::NAME,
        PersonDescribed::NAME,
        PersonAskedForTheirArchive::NAME,
        PersonTookTheirArchive::NAME,
        PersonDroppedTheirArchive::NAME,
    ];
}
