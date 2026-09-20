<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Credential;

use Heidi\Core\Domain\Credential\Credential;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Exception\CredentialEnrolmentFailedException;
use Heidi\Core\Exception\KmsUnavailableException;

/**
 * How a user gets a credential of a kind, in two steps: the provider issues what the user acts on (the
 * creation options of a passkey, the secret of a TOTP), the user answers, the credential exists once the
 * answer holds. One implementation per kind, in the core; the integration serialises the challenge for the
 * browser and hands the answer back as the browser sent it. An abandoned enrolment leaves nothing behind.
 *
 * @template TChallenge of object
 */
interface CredentialEnrolmentInterface
{
    /**
     * @return class-string<Credential>
     */
    public function kind(): string;

    /**
     * @throws CredentialEnrolmentFailedException when the user cannot get another credential of this kind
     * @return TChallenge
     */
    public function begin(User $user): object;

    /**
     * @param TChallenge $challenge what begin() issued
     * @param string $answer what the user sent back, as the integration received it
     *
     * @throws CredentialEnrolmentFailedException
     * @throws KmsUnavailableException when the credential is kept under the KMS of the tenant and the KMS does not answer; nothing is written
     */
    public function complete(User $user, string $name, object $challenge, string $answer, RequestContext $context): Credential;
}
