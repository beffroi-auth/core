<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Key;

use Heidi\Core\Domain\Event\SigningKeyRotated;
use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\LogicException;
use Heidi\Core\Port\KeyPairGeneratorInterface;
use Heidi\Core\Port\KeySealerInterface;
use Heidi\Core\Port\SigningKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Rotation without an outage: the new key signs at once, the previous one stays published while the tokens
 * it signed are alive, and leaves the JWK Set when told so. Clients that cache the JWK Set refetch it on an
 * unknown kid (oidc_login does, with a 60 s cooldown); the rotation window covers that.
 */
final readonly class SigningKeyRotator
{
    public function __construct(
        private SigningKeyRepositoryInterface $keys,
        private KeyPairGeneratorInterface $generator,
        private KeySealerInterface $sealer,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * Generates a key, seals it, makes it the active one; the key that was active starts retiring.
     */
    public function rotate(Tenant $tenant, SignatureAlgorithm $algorithm, RequestContext $context): SigningKey
    {
        $now = $this->clock->now();
        $pair = $this->generator->generate($algorithm);
        $key = new SigningKey($pair->kid, $tenant, $algorithm, $pair->publicJwk, $this->sealer->seal($pair->privateJwk), $now);

        $previous = $this->keys->findActive($tenant, $algorithm);
        if ($previous !== null) {
            $previous->startRetiring($now);
            $this->keys->save($previous);
        }
        $this->keys->save($key);
        $this->events->dispatch(new SigningKeyRotated($key, $previous, $now, $context));

        return $key;
    }

    /**
     * Removes a retiring key from the JWK Set. The caller decides when, once the last token signed with it
     * has expired.
     */
    public function retire(Tenant $tenant, string $kid): SigningKey
    {
        $key = $this->keys->find($tenant, $kid);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" has no key "%s".', $tenant->slug, $kid));
        }
        if ($key->isActive()) {
            throw new LogicException('The active key cannot be retired; rotate first.');
        }
        $key->retire($this->clock->now());
        $this->keys->save($key);

        return $key;
    }
}
