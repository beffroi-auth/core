<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use function array_any;
use function array_key_exists;
use function array_values;
use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function sprintf;

/**
 * Answers for the domains it declares, compared exactly: a subdomain is not its parent.
 */
final readonly class UpstreamProvider
{
    public const string EMAIL = 'email';

    public const string PHONE_NUMBER = 'phone_number';

    /**
     * @var list<EmailDomain>
     */
    public array $domains;

    /**
     * @var list<string> OIDC Core §5.1 names
     */
    public array $claims;

    /**
     * @param list<EmailDomain> $domains
     * @param list<string> $claims OIDC Core §5.1 names
     *
     * @throws InvalidArgumentException when it is unnamed, or a claim is none of the profile
     */
    public function __construct(
        public string $name,
        array $domains,
        array $claims = [],
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('An upstream provider is named.');
        }
        $folded = [];
        foreach ($domains as $domain) {
            $folded[$domain->value] ??= $domain;
        }
        $profile = Profile::empty()->claims();
        $filled = [];
        foreach ($claims as $claim) {
            if (! array_key_exists($claim, $profile)) {
                throw new InvalidArgumentException(sprintf('The upstream provider "%s" fills "%s", which is no claim of the profile.', $name, $claim));
            }
            if (! in_array($claim, $filled, true)) {
                $filled[] = $claim;
            }
        }
        $this->domains = array_values($folded);
        $this->claims = $filled;
    }

    public function answersFor(EmailDomain $domain): bool
    {
        return array_any($this->domains, static fn (EmailDomain $declared): bool => $declared->equals($domain));
    }

    /**
     * @param array<string, string> $said by OIDC Core §5.1 name
     */
    public function source(array $said, bool $saidAPhoneNumber = false): UserSource
    {
        $held = [self::EMAIL];
        foreach ($this->claims as $claim) {
            if (array_key_exists($claim, $said)) {
                $held[] = $claim;
            }
        }
        if ($saidAPhoneNumber) {
            $held[] = self::PHONE_NUMBER;
        }

        return UserSource::upstream($this->name, $held);
    }

    /**
     * A person an import linked to this provider is not one it is the source of.
     */
    public function isTheSourceOf(User $user): bool
    {
        return $user->source->provider === $this->name;
    }
}
