<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * Why a string is not a password this provider will hold (NIST SP 800-63B §5.1.1.2). A closed set the
 * page and the console that pose one read to say which rule pronounced it: nothing acts on it, every
 * refusal being the same answer, which is that nothing was written.
 *
 * Not PasswordChangeRefusal, which answers another question: that one says why a person was not allowed
 * to replace their own password, this one says why the string they posed is not a password at all, and
 * the console that writes a password for somebody meets this one and never that one.
 */
enum PasswordRefusal: string
{
    /**
     * Shorter than the eight characters of §5.1.1.2.
     */
    case TooShort = 'too_short';

    /**
     * Longer than anything a hasher is asked to swallow.
     */
    case TooLong = 'too_long';

    /**
     * Within the two lengths and still guessable: short of alphabet and short of length at once
     * (PasswordStrength). Never a composition rule, which the same section asks a verifier not to impose.
     */
    case TooWeak = 'too_weak';

    /**
     * Counted in a list of values known to be compromised, which is the rule §5.1.1.2 puts first.
     */
    case Breached = 'breached';

    /**
     * The list could not be asked, or answered something that is not a list. An unknown is not a clean
     * answer (decision 0006): nothing is written, and whoever posed the password is told to pose it again
     * rather than handed a password nobody checked.
     */
    case NotChecked = 'not_checked';
}
