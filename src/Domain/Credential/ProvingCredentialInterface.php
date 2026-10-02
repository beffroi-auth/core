<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Session\AuthenticationMethod;

/**
 * A credential a user can authenticate with, which says what such an authentication may prove, as "amr"
 * values (RFC 8176 §2): "pwd" for a password; "hwk" or "swk" as the backup eligibility of the passkey
 * says, and "user", for a passkey; "otp" for a TOTP. What this user can still prove, and so which class
 * of the policy a step-up may reach for them (decision 0010, architecture §4.3), is read here credential
 * by credential, never from a kind.
 */
interface ProvingCredentialInterface
{
    /**
     * Empty for a credential that authenticates nobody any more, a revoked one.
     *
     * @return list<AuthenticationMethod>
     */
    public function provableMethods(): array;
}
