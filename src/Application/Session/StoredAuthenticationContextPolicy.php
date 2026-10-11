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
 * The document the "acr" policy of a tenant is kept as (decision 0010): its classes in order, each a name
 * on a predicate of the provider.
 *
 * A list rather than an object keyed by name, because "1" and "2" are names and a JSON object key that
 * looks like a number comes back as an integer.
 *
 * A predicate is written under the name it gives itself and read back as the class the wiring holds for
 * that name, which is why this is a use case and not a method of the policy: a policy naming a predicate
 * this deployment is not wired with is refused rather than guessed, as a stored password hash naming an
 * algorithm nothing carries is (decision 0002).
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
     * @throws InvalidArgumentException when a class of the document is not a name on a predicate, when
     *                                 nothing wired carries the predicate it names, or when what the
     *                                 document holds is not a policy this provider would have accepted
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
