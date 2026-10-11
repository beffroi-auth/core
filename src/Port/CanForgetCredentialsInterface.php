<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A companion of CredentialRepositoryInterface: what the hardening of a tenant asks of the store
 * (issue #327, TenantHardening). The credentials of a tenant, whoever holds them and whatever their kind,
 * and the removal of one.
 *
 * It removes where CredentialRevocation marks: a credential the floor of a tenant forbids is not a
 * credential that will never be used again, it is a credential this provider must not hold, secret
 * included. The act survives in the journal of the person (CredentialForgotten), the row does not.
 */
interface CanForgetCredentialsInterface
{
    /**
     * Every credential of the tenant, read in batches so that a large tenant is walked without being held
     * in memory; the order is the store's.
     *
     * @return iterable<Credential>
     */
    public function credentialsOf(Tenant $tenant): iterable;

    /**
     * The row goes. A credential already gone is forgotten again without complaint, so that a sweep
     * interrupted halfway is run again.
     */
    public function forget(Credential $credential): void;
}
