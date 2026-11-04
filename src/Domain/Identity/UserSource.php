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

final readonly class UserSource
{
    /**
     * @var list<string> OIDC Core §5.1 names
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

    public static function local(): self
    {
        return new self();
    }

    /**
     * @param list<string> $claims OIDC Core §5.1 names
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
     * @param array<string, string> $said by OIDC Core §5.1 name
     *
     * @throws InvalidArgumentException when a claim it holds is said in a form the profile refuses
     */
    public function rewrite(Profile $profile, array $said): Profile
    {
        $written = [];
        foreach ($said as $claim => $value) {
            if ($this->holds($claim)) {
                $written[$claim] = $value;
            }
        }

        return $profile->describedWith($written);
    }

    /**
     * What a source holds is its own: the next sign-in would write it back, a client reading the edit meanwhile.
     *
     * @param list<string> $changed
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
     * @param array<array-key, mixed>|null $document
     *
     * @throws InvalidArgumentException
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
