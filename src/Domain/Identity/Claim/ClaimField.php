<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
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
 * class per kind: what behaves differently is one branch of the template, choices or no choices, and
 * nothing of this provider reads a kind to decide anything.
 */
final readonly class ClaimField
{
    /**
     * The kinds an input of these pages carries, which are the ones a browser understands without a
     * script: a line of text, a telephone number, a URL.
     */
    public const array KINDS = ['text', 'tel', 'url'];

    /**
     * @param list<string> $choices the values this claim may hold, empty when the set is not closed
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
     * One of a closed set, which the page offers as a choice of them rather than as something to type.
     *
     * @param list<string> $choices
     */
    public static function oneOf(array $choices): self
    {
        if ($choices === []) {
            throw new InvalidArgumentException('A claim offered as a choice offers something to choose.');
        }

        return new self('text', $choices);
    }
}
