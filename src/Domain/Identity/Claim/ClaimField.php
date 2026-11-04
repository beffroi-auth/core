<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_key_exists;
use function count;
use function in_array;
use function preg_match;
use function sprintf;

final readonly class ClaimField
{
    public const array KINDS = ['text', 'tel', 'url', 'date'];

    /**
     * A longer set is typed into with suggestions rather than chosen from a list: twenty fit an open dropdown.
     */
    public const int PICKED_BEYOND = 20;

    /**
     * HTML §2.3.5.1, §2.3.5.2
     *
     * @var array<string, non-empty-string> by kind, the pattern of what its input can carry
     */
    private const array CARRIED = [
        'date' => '/^(?!0000)\\d{4}-\\d{2}-\\d{2}$/D',
    ];

    /**
     * @param array<string, string> $choices label by value, in the order offered; empty for an open set
     * @param string|null $pattern an HTML "pattern" attribute
     * @param string|null $example a translation key for an example of the value
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
     * The pattern is for a browser without a date input, which renders a line of text instead.
     */
    public static function date(?string $pattern = null, ?string $example = null): self
    {
        return new self('date', [], $pattern, $example);
    }

    /**
     * @param array<string, string> $choices label by value
     */
    public static function oneOf(array $choices): self
    {
        if ($choices === []) {
            throw new InvalidArgumentException('A claim offered as a choice offers something to choose.');
        }

        return new self('text', $choices);
    }

    public function isPicked(): bool
    {
        return count($this->choices) > self::PICKED_BEYOND;
    }

    public function holds(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return $this->choices === [] || array_key_exists($value, $this->choices);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertHolds(?string $value): void
    {
        if (! $this->holds($value)) {
            throw new InvalidArgumentException(sprintf('The value "%s" is not one this claim may hold.', $value));
        }
    }

    /**
     * Offered as text when a date input cannot carry the value: it would render empty and a save would erase it.
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
