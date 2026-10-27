<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_key_exists;
use function count;
use function in_array;
use function preg_match;
use function sprintf;

/**
 * What kind of field a claim is offered as (issue #476): what a person types into, and what the browser
 * may check before anything is sent.
 *
 * **A value and not a form.** Neither this package nor the bundle knows what a form is: the pages of the
 * interaction plane are bare HTML with a form token, which is what lets an application dress them by one
 * layout override and no form theme (architecture §5.2). So a claim says the kind its input carries, the
 * values when the set is closed, and the pattern or the length its value holds to; the page prints them
 * as it prints the codes of every other element.
 *
 * The kind is a value the page carries and nothing acts on, which is why it is a string here and not a
 * class per kind: what behaves differently is a branch of the template, and nothing of this provider
 * reads a kind to decide anything.
 *
 * **A closed set is closed on the server too** (issue #479). The set is what the claim says it holds, so
 * what refuses a value outside it is the claim and not the page: a page that offers a list is a page whose
 * form can be sent with anything (decision 0006).
 *
 * **How long a set is, is a property of the set.** A page asks `isPicked()` rather than counting: a
 * handful of values is chosen from a list, several hundred are typed with the set suggested, and which
 * one a claim gets follows from what it holds rather than from a number written on a page.
 */
final readonly class ClaimField
{
    /**
     * The kinds an input of these pages carries, which are the ones a browser understands without a
     * script: a line of text, a telephone number, a URL, a date.
     */
    public const array KINDS = ['text', 'tel', 'url', 'date'];

    /**
     * Beyond how many values a set is picked from rather than chosen from. Twenty is what a person reads
     * in an open dropdown without scrolling it; the two sets that pass it are the names of the zoneinfo
     * database and the locales ICU has data for, both of which are several hundred.
     */
    public const int PICKED_BEYOND = 20;

    /**
     * What an input of a kind can carry, where that is narrower than what the model holds: a date input
     * carries a whole date, and OIDC Core §5.1 provides for a year alone (`forHolding()`).
     *
     * A list of the kinds that have one and not a method per kind, because it is a fact about a kind and
     * not a behaviour of it: the day a second kind is narrower than its claim, it is a line here.
     *
     * @var array<string, non-empty-string>
     */
    private const array CARRIED = [
        'date' => '/^\\d{4}-\\d{2}-\\d{2}$/D',
    ];

    /**
     * @param array<string, string> $choices the values this claim may hold, each with the label a person
     *                                       reads it by, in the order they are offered; empty when the
     *                                       set is not closed
     * @param string|null $pattern what the value holds to (HTML, "pattern"), null when nothing does
     * @param string|null $example a key the page translates into an example of the value, null for none
     */
    private function __construct(
        public string $kind,
        public array $choices = [],
        public ?string $pattern = null,
        public ?string $example = null,
    ) {
        if (! in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException(sprintf('A claim is not offered as a "%s" field.', $kind));
        }
    }

    public static function text(?string $pattern = null, ?string $example = null): self
    {
        return new self('text', [], $pattern, $example);
    }

    public static function telephone(): self
    {
        return new self('tel');
    }

    public static function url(): self
    {
        return new self('url');
    }

    /**
     * A date, which a browser offers a calendar for. The pattern comes with it for a browser that has no
     * date input and renders a line of text instead, where it is the only check left.
     */
    public static function date(?string $pattern = null, ?string $example = null): self
    {
        return new self('date', [], $pattern, $example);
    }

    /**
     * One of a closed set, each value with the label a person reads it by: the name of a language in that
     * language is the only thing recognisable in a list of several hundred, and a name of the zoneinfo
     * database is its own label.
     *
     * @param array<string, string> $choices
     */
    public static function oneOf(array $choices): self
    {
        if ($choices === []) {
            throw new InvalidArgumentException('A claim offered as a choice offers something to choose.');
        }

        return new self('text', $choices);
    }

    /**
     * Whether the set is too long to be a list somebody reads, and is therefore typed into with the whole
     * set suggested.
     */
    public function isPicked(): bool
    {
        return count($this->choices) > self::PICKED_BEYOND;
    }

    /**
     * Whether this field holds that value: a closed set holds what is in it, and nothing else holds
     * anything in particular, the shape of a value being the model's and not a field's. Taking a value
     * back is holdable by every field.
     */
    public function holds(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return $this->choices === [] || array_key_exists($value, $this->choices);
    }

    /**
     * @throws InvalidArgumentException when the value is not one this field holds
     */
    public function assertHolds(?string $value): void
    {
        if (! $this->holds($value)) {
            throw new InvalidArgumentException(sprintf('The value "%s" is not one this claim may hold.', $value));
        }
    }

    /**
     * The field a page offers for a value already held, which is this one unless the input cannot carry
     * that value: a birthdate held as a year alone is offered as a line of text, because a date input
     * renders it empty and a save on an empty input would quietly erase what an import wrote.
     */
    public function forHolding(?string $value): self
    {
        $carried = self::CARRIED[$this->kind] ?? null;
        if ($value === null || $value === '' || $carried === null || preg_match($carried, $value) === 1) {
            return $this;
        }

        return new self('text', $this->choices, $this->pattern, $this->example);
    }
}
