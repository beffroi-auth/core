<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use function array_filter;
use function array_values;
use Beffroi\Core\Domain\Listing\Period;
use function in_array;
use Symfony\Component\Uid\Uuid;

/**
 * What a list of credentials is narrowed to, a criterion left null admitting every credential. A credential
 * that cannot say what a criterion asks, a password asked for its model, is not admitted by it.
 */
final readonly class CredentialQuestion
{
    /**
     * @param Period|null $used a credential never used being in no period
     * @param bool|null $revoked the revoked ones alone when true, the others alone when false
     * @param Uuid|null $model an AAGUID (WebAuthn Level 3 §6.5.1)
     * @param bool|null $synced backup eligible (WebAuthn Level 3 §6.1.3) or bound to its device
     */
    public function __construct(
        public ?Period $enrolled = null,
        public ?Period $used = null,
        public ?bool $revoked = null,
        public ?Uuid $model = null,
        public ?bool $synced = null,
        public ?AuthenticatorTransport $reachedThrough = null,
    ) {
    }

    public function admits(Credential $credential): bool
    {
        return (
            $this->enrolled?->holds($credential->createdAt) !== false
            && $this->wasUsedWithin($credential)
            && $this->isInTheStateAsked($credential)
            && $this->isOfTheModelAsked($credential)
            && $this->isSyncedAsAsked($credential)
            && $this->isReachedAsAsked($credential)
        );
    }

    /**
     * @template T of Credential
     *
     * @param list<T> $credentials
     *
     * @return list<T> those it admits, the revoked ones after the others, each part in the order given
     */
    public function narrow(array $credentials): array
    {
        $admitted = array_filter($credentials, $this->admits(...));

        return [
            ...array_values(array_filter($admitted, static fn (Credential $credential): bool => ! self::isRevoked($credential))),
            ...array_values(array_filter($admitted, self::isRevoked(...))),
        ];
    }

    private function wasUsedWithin(Credential $credential): bool
    {
        return $this->used === null || $credential->lastUsedAt !== null && $this->used->holds($credential->lastUsedAt);
    }

    private function isInTheStateAsked(Credential $credential): bool
    {
        return $this->revoked === null || self::isRevoked($credential) === $this->revoked;
    }

    private function isOfTheModelAsked(Credential $credential): bool
    {
        return $this->model === null || $credential instanceof ModelDeclaringCredentialInterface && $credential->aaguid->equals($this->model);
    }

    private function isSyncedAsAsked(Credential $credential): bool
    {
        return $this->synced === null || $credential instanceof SyncableCredentialInterface && $credential->backupEligible === $this->synced;
    }

    private function isReachedAsAsked(Credential $credential): bool
    {
        return (
            $this->reachedThrough === null
            || $credential instanceof ReachableCredentialInterface
            && in_array($this->reachedThrough, $credential->reachedThrough, true)
        );
    }

    private static function isRevoked(Credential $credential): bool
    {
        return $credential instanceof RevocableCredentialInterface && $credential->isRevoked();
    }
}
