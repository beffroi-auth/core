<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Session;

use function array_map;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Domain\Session\AuthenticationContextClass;
use Beffroi\Core\Domain\Session\AuthenticationContextPolicy;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AuthenticationPredicatesInterface;

/**
 * The document a tenant's "acr" policy is stored as: its classes in order, each a name on a wired predicate.
 *
 * A list rather than an object keyed by name: "1" is a name, and a JSON object key that looks like a number
 * comes back as an integer.
 */
final readonly class StoredAuthenticationContextPolicy
{
    private const string CLASSES = 'classes';

    private const string NAME = 'name';

    private const string PREDICATE = 'predicate';

    public function __construct(
        private AuthenticationPredicatesInterface $predicates,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function document(AuthenticationContextPolicy $policy): array
    {
        return [
            self::CLASSES => array_map(static fn (AuthenticationContextClass $class): array => [
                self::NAME => $class->value,
                self::PREDICATE => $class->predicate->name(),
            ], $policy->classes),
        ];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when the document is not a policy, or names a predicate nothing wired carries
     */
    public function of(array $document): AuthenticationContextPolicy
    {
        $classes = [];
        foreach (new Document($document)->entries(self::CLASSES) as $entry) {
            $members = Document::of($entry, 'authentication context class');
            $name = $members->nullableString(self::NAME);
            $predicate = $members->nullableString(self::PREDICATE);
            if ($name === null || $predicate === null) {
                throw new InvalidArgumentException('An authentication context policy lists classes, each a name on a predicate of the provider.');
            }
            $classes[] = new AuthenticationContextClass($name, $this->predicates->named($predicate));
        }

        return new AuthenticationContextPolicy($classes);
    }
}
