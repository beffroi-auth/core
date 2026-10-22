<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function array_values;
use function implode;
use function in_array;
use function is_string;
use function sprintf;

/**
 * The source that created a person and holds its attributes (decision 0002): local, for a person a console,
 * a fixture or an import created, or an upstream provider, named as a linked identity names its own
 * (`LinkedIdentity::$provider`), with the claims of OIDC Core §5.1 it holds of that person, by their names.
 *
 * **Not an enumeration, as the source of a group is.** An upstream provider is what a deployment declares,
 * so a set the wiring decides: SCIM and an OpenID Connect federation are one more provider each, a name and
 * the claims it holds, and no case of a class is added for either.
 *
 * **What a source holds, a manual description does not change** (`assertLeaves()`): the next sign-in
 * through that provider would write it again, and a client would read the edit in between. A local source
 * holds nothing, so whatever a description says of a local person is written.
 */
final readonly class UserSource
{
    /**
     * @var list<string> the names of the claims the provider holds, each once, in the order it named them
     */
    public array $claims;

    /**
     * @param list<string> $claims
     *
     * @throws InvalidArgumentException when a provider is unnamed, a claim is unnamed, or a local source holds a claim
     */
    public function __construct(
        public ?string $provider = null,
        array $claims = [],
    ) {
        if ($provider === '') {
            throw new InvalidArgumentException('An upstream provider that created a person is named.');
        }
        if ($provider === null && $claims !== []) {
            throw new InvalidArgumentException('A local source holds no claim of the person it created.');
        }
        $held = [];
        foreach ($claims as $claim) {
            if ($claim === '') {
                throw new InvalidArgumentException('A claim an upstream provider holds has a name.');
            }
            if (! in_array($claim, $held, true)) {
                $held[] = $claim;
            }
        }
        $this->claims = $held;
    }

    /**
     * A person a console, a fixture or an import created, which is every person no provider created.
     */
    public static function local(): self
    {
        return new self();
    }

    /**
     * @param list<string> $claims the names of the claims of OIDC Core §5.1 the provider holds
     */
    public static function upstream(string $provider, array $claims): self
    {
        return new self($provider, $claims);
    }

    public function isLocal(): bool
    {
        return $this->provider === null;
    }

    public function holds(string $claim): bool
    {
        return in_array($claim, $this->claims, true);
    }

    /**
     * The profile as this source writes it from what its provider says now: each claim of the profile it
     * holds said as the provider says it, and cleared when the provider says nothing of it, the provider
     * being the one that answers for it; every other claim left as it was. A local source holds nothing, so
     * the profile comes back as it was.
     *
     * @param array<string, string> $said what the provider said, by the names of OIDC Core §5.1
     *
     * @throws InvalidArgumentException when a claim it holds is said in a form the profile refuses
     */
    public function rewrite(Profile $profile, array $said): Profile
    {
        $written = [];
        foreach ($profile->claims() as $claim => $value) {
            if ($this->holds($claim)) {
                $written[$claim] = $said[$claim] ?? null;
            }
        }

        return $profile->describedWith($written);
    }

    /**
     * A manual description that changes these claims of the person, refused when the source holds any of
     * them, naming the provider and what it holds.
     *
     * @param list<string> $changed the names of the claims the description says otherwise
     *
     * @throws InvalidArgumentException when the source holds one of them
     */
    public function assertLeaves(array $changed): void
    {
        $held = [];
        foreach ($changed as $claim) {
            if ($this->holds($claim)) {
                $held[$claim] = $claim;
            }
        }
        if ($held === []) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'The upstream provider "%s" created this person and holds "%s": a manual description does not change what its source holds.',
            $this->provider,
            implode('", "', array_values($held)),
        ));
    }

    /**
     * The document a column holds it as: the provider and the claims it holds, and nothing for a local
     * source, which holds nothing and has nothing to write.
     *
     * @return array{provider: string, claims: list<string>}|null
     */
    public function toDocument(): ?array
    {
        if ($this->provider === null) {
            return null;
        }

        return [
            'provider' => $this->provider,
            'claims' => $this->claims,
        ];
    }

    /**
     * No document is a local source; a document names its provider, or it is not one this object wrote.
     *
     * @param array<array-key, mixed>|null $document
     *
     * @throws InvalidArgumentException when the document is not what this object writes
     */
    public static function fromDocument(?array $document): self
    {
        if ($document === null) {
            return self::local();
        }
        $read = new Document($document);
        $provider = $read->nullableString('provider');
        if ($provider === null) {
            throw new InvalidArgumentException('The document of an upstream source names its provider.');
        }
        $claims = [];
        foreach ($read->entries('claims') as $claim) {
            if (! is_string($claim)) {
                throw new InvalidArgumentException('The claims an upstream provider holds are names.');
            }
            $claims[] = $claim;
        }

        return new self($provider, $claims);
    }
}
