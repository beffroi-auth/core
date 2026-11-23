<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Domain\Credential\ModelDeclaringCredentialInterface;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AuthenticatorModelSourceInterface;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;

/**
 * Without attestation an AAGUID is not provably authentic (WebAuthn Level 3 §6): no login is decided on a model.
 * An all-zero AAGUID names no model: Level 2 clients sent it instead of the real one without attestation (§18.1.1).
 */
final readonly class AuthenticatorModels
{
    /**
     * @param iterable<AuthenticatorModelSourceInterface> $sources
     */
    public function __construct(
        private iterable $sources,
    ) {
    }

    /**
     * @throws RuntimeException when a source cannot be read
     */
    public function of(ModelDeclaringCredentialInterface $credential): ?AuthenticatorModel
    {
        return $this->named($credential->aaguid);
    }

    /**
     * @throws RuntimeException when a source cannot be read
     */
    public function named(Uuid $aaguid): ?AuthenticatorModel
    {
        if ($aaguid->equals(new NilUuid())) {
            return null;
        }
        foreach ($this->sources as $source) {
            $model = $source->model($aaguid);
            if ($model !== null) {
                return $model;
            }
        }

        return null;
    }
}
