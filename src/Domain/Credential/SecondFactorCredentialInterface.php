<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

/**
 * A credential that completes a password login rather than replacing it: a TOTP. A password alone does
 * not sign in a user who holds an active one (architecture §3.2); a session opened on the password of
 * such a user before the second factor was proven would be a downgrade for every client of the tenant.
 * A second factor is taken out of use by revocation, and a revoked one requires nothing. A passkey is
 * not one: it is a first factor, and proves what its authenticator says.
 */
interface SecondFactorCredentialInterface extends RevocableCredentialInterface
{
}
