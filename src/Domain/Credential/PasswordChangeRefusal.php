<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * Why a person was not allowed to change their own password (architecture §3.1). A closed set the page
 * that offers the change reads to say which of them it was: nothing acts on it, the refusal being the
 * same answer in every case, which is that the password did not change.
 */
enum PasswordChangeRefusal: string
{
    /**
     * What was typed as the password in use is not it. The one refusal that matters: a session somebody
     * else took over does not get to replace the password it signed in with.
     */
    case WrongPassword = 'wrong_password';

    /**
     * The password posed is the one already held, which is a change that changes nothing.
     */
    case SameAsBefore = 'same_as_before';

    /**
     * This person holds no password at all, signing in by another means: there is nothing to replace,
     * and the page offers nothing rather than setting a first one from a form nobody proved anything on.
     */
    case NoPasswordHeld = 'no_password_held';

    /**
     * This person is of an e-mail domain the tenant federates to an upstream provider, through which alone
     * they sign in (decision 0029): a password they held before their domain was federated is replaced by
     * nobody, posing a new one being posing a local credential for them.
     */
    case FederatedDomain = 'federated_domain';
}
