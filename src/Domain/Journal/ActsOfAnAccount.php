<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Event\AuthenticationFailed;
use Beffroi\Core\Domain\Event\AuthenticationSessionEnded;
use Beffroi\Core\Domain\Event\BackupCodeUsed;
use Beffroi\Core\Domain\Event\BackupCodesRenewed;
use Beffroi\Core\Domain\Event\CredentialEnrolled;
use Beffroi\Core\Domain\Event\CredentialRenamed;
use Beffroi\Core\Domain\Event\CredentialRevoked;
use Beffroi\Core\Domain\Event\PasskeyUsed;
use Beffroi\Core\Domain\Event\PasswordChanged;
use Beffroi\Core\Domain\Event\PasswordUsed;
use Beffroi\Core\Domain\Event\PersonAskedForTheirArchive;
use Beffroi\Core\Domain\Event\PersonDescribedThemselves;
use Beffroi\Core\Domain\Event\PersonDroppedTheirArchive;
use Beffroi\Core\Domain\Event\PersonTookTheirArchive;
use Beffroi\Core\Domain\Event\TotpUsed;
use Beffroi\Core\Domain\Event\UserAuthenticated;

/**
 * Which acts of the journal are what happened to an account (issue #571): what the page a person opens of
 * their own journal asks for, and what the command that reads it over a period asks for when it is asked
 * for nothing in particular.
 *
 * **The acts are named and the question is not the whole journal**, because the journal of a person holds
 * the protocol as well: a token issued, a consent granted, a read that handed them over to an application
 * are acts of theirs too (issues #588 to #590), and they are read where they mean something, by
 * application, beside what that application was given. What is here is the account itself: who got in and
 * how, who tried, what signed out, what changed of the means of signing in, and what the person did of
 * their own record.
 *
 * **It is one list and a walk holds it to the events**: every act of this package that names a person is
 * here or is named in the test as one that is not theirs, so an act added to the package is put on the
 * page deliberately rather than forgotten (ActsOfAnAccountTest).
 */
final class ActsOfAnAccount
{
    /**
     * @var list<string>
     */
    public const array NAMES = [
        // who got in, and who tried
        UserAuthenticated::NAME,
        AuthenticationFailed::NAME,
        // how: every proof is an act of its own, and no column holds a mode
        PasswordUsed::NAME,
        PasskeyUsed::NAME,
        TotpUsed::NAME,
        BackupCodeUsed::NAME,
        // what closed
        AuthenticationSessionEnded::NAME,
        // what changed of the means of signing in
        CredentialEnrolled::NAME,
        CredentialRevoked::NAME,
        CredentialRenamed::NAME,
        PasswordChanged::NAME,
        BackupCodesRenewed::NAME,
        // what the person did of their own record
        PersonDescribedThemselves::NAME,
        PersonAskedForTheirArchive::NAME,
        PersonTookTheirArchive::NAME,
        PersonDroppedTheirArchive::NAME,
    ];
}
