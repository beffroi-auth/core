<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\BreachCount;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Domain\Credential\PasswordRefusal;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Exception\BreachLookupFailedException;
use Beffroi\Core\Exception\PasswordRefusedException;
use Beffroi\Core\Port\BreachedPasswordsInterface;
use SensitiveParameter;

/**
 * The rule NIST SP 800-63B §5.1.1.2 puts first: a prospective secret is compared against a list of values
 * known to be commonly used, expected or compromised, and refused when it is one.
 *
 * It is one object and not a block in each use case, because every path that writes a password runs it:
 * the console of an operator (UserProvisioner::setPassword()), the page where a person replaces their own
 * (PasswordChange), and whatever poses one next.
 *
 * **An unknown is refused.** A timeout or a body that is not a list is BreachLookupFailedException, and
 * what it becomes here is a refusal naming it: nothing is written, and whoever posed the password poses it
 * again. Writing one the provider could not check and saying so in a line would be the option to do it
 * wrong (decision 0006).
 */
final readonly class BreachedPasswordRefusal
{
    public function __construct(
        private BreachedPasswordsInterface $breaches,
    ) {
    }

    /**
     * What the list said of this password, so that the caller writes with it what it asked: a count that
     * was taken is the one thing a credential may be marked with.
     *
     * @throws PasswordRefusedException when the list counts the password, or could not be asked
     */
    public function count(#[SensitiveParameter] PlainPassword $password): BreachCount
    {
        if (! $this->breaches->looksUp()) {
            return BreachCount::nothingAsked();
        }
        try {
            $count = $this->breaches->timesSeen(PasswordDigest::of($password->value));
        } catch (BreachLookupFailedException $failure) {
            throw new PasswordRefusedException(
                PasswordRefusal::NotChecked,
                'The list of compromised passwords could not be asked: the password is not written. Try again.',
                previous: $failure,
            );
        }
        if ($count->isBreached()) {
            // how many times the list counts it is not said: the answer is the same at one as at a
            // million, and a count on its way to a terminal or a page is a figure nobody acts on
            throw new PasswordRefusedException(
                PasswordRefusal::Breached,
                'This password is in a list of compromised passwords: somebody else already holds it. Pose another one.',
            );
        }

        return $count;
    }
}
