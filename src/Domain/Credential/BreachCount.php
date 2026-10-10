<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * What a list of compromised passwords says of one password: how many times it is counted there, or that
 * nothing was asked because this deployment names no list.
 *
 * **The three answers are three answers.** Counted, not counted, and nothing asked are told apart here
 * rather than at every call site, so that a deployment which looks nothing up cannot read as a deployment
 * that looked and found nothing (decision 0006: no boolean coming from a third party is ever cast). What a
 * list could not answer at all is neither of the three and never reaches this object: it is
 * BreachLookupFailedException, which the port declares.
 */
final readonly class BreachCount
{
    private function __construct(
        /**
         * How many times the list counts the password, null when no list was asked.
         */
        public ?int $times,
    ) {
    }

    /**
     * @throws InvalidArgumentException when the count is negative, which no list answers
     */
    public static function times(int $times): self
    {
        if ($times < 0) {
            throw new InvalidArgumentException('A list counts a password zero times or more.');
        }

        return new self($times);
    }

    /**
     * This deployment names no list: nothing left it and nothing is known of the password.
     */
    public static function nothingAsked(): self
    {
        return new self(null);
    }

    public function wasAsked(): bool
    {
        return $this->times !== null;
    }

    /**
     * Counted at least once, which is a password somebody else already holds the plaintext of.
     */
    public function isBreached(): bool
    {
        return $this->times !== null && $this->times > 0;
    }
}
