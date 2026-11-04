<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateTimeImmutable;
use Beffroi\Core\Exception\LogicException;
use function sprintf;

abstract class RevocableCredential extends Credential implements RevocableCredentialInterface
{
    public private(set) ?DateTimeImmutable $revokedAt = null;

    public function revoke(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->revokedAt = $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * @throws LogicException
     */
    protected function refuseIfRevoked(): void
    {
        if ($this->revokedAt !== null) {
            throw new LogicException(sprintf('A revoked %s never authenticates again.', $this->kind()));
        }
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            ...parent::classifiedFields(),
            'revokedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
        ];
    }
}
