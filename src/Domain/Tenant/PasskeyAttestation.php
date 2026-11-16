<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use function array_map;
use Beffroi\Core\Domain\Credential\Attestation\AttestationEvidence;
use Beffroi\Core\Domain\Credential\Attestation\AttestationInterface;
use Beffroi\Core\Domain\Credential\Attestation\NoAttestation;
use Beffroi\Core\Domain\Credential\Attestation\PasskeyAttestationPolicyInterface;
use Beffroi\Core\Domain\Credential\Attestation\PasskeyAttestationRefusal;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\PasskeyAttestationRefusedException;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use DateTimeImmutable;
use function sprintf;

/**
 * What the enrolment of a passkey asks of its authenticator in this tenant, and what it admits (decision 0046).
 */
final readonly class PasskeyAttestation
{
    /**
     * @param list<AttestationRoot> $roots the roots of enterprise attestations, under which the serial number is required
     * @param list<AttestationRoot> $ownRoots the authorities of the tenant itself, under which it is not
     * @param list<AdmittedModelUnderAlert> $modelsUnderAlert
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public PasskeyAttestationPolicyInterface $policy,
        public array $roots = [],
        public array $ownRoots = [],
        public array $modelsUnderAlert = [],
    ) {
        $named = [];
        foreach ($modelsUnderAlert as $model) {
            $aaguid = $model->aaguid->toRfc4122();
            if (isset($named[$aaguid])) {
                throw new InvalidArgumentException(sprintf('The model "%s" is admitted under its security alert twice.', $aaguid));
            }
            $named[$aaguid] = true;
        }
        $policy->assertReads($this);
    }

    public static function none(): self
    {
        return new self(new NoAttestation());
    }

    /**
     * @throws PasskeyAttestationRefusedException
     * @throws PasskeyCeremonyFailedException
     */
    public function judge(AttestationInterface $seen, DateTimeImmutable $now): AttestationEvidence
    {
        return $this->policy->judge($this, $seen, $now);
    }

    /**
     * FIDO Metadata Service v3.1.1 §3.1.4.2: whether the model is under a security alert the tenant still admits.
     *
     * @throws PasskeyAttestationRefusedException when it is under one the tenant does not admit, or no longer
     */
    public function admitsTheAlertOf(AttestationInterface $seen, DateTimeImmutable $now): bool
    {
        if (! $seen->isUnderSecurityAlert()) {
            return false;
        }
        foreach ($this->modelsUnderAlert as $model) {
            if ($model->admits($seen->aaguid, $now)) {
                return true;
            }
        }

        throw new PasskeyAttestationRefusedException(
            PasskeyAttestationRefusal::ModelUnderSecurityAlert,
            sprintf('The last status report of the model "%s" is a security notification (FIDO Metadata Service v3.1.1 §3.1.4.2).', $seen->aaguid->toRfc4122()),
        );
    }

    public function equals(self $other): bool
    {
        return (
            $this->policy->name() === $other->policy->name()
            && self::pems($this->roots) === self::pems($other->roots)
            && self::pems($this->ownRoots) === self::pems($other->ownRoots)
            && self::days($this->modelsUnderAlert) === self::days($other->modelsUnderAlert)
        );
    }

    /**
     * @param list<AttestationRoot> $roots
     *
     * @return list<string>
     */
    private static function pems(array $roots): array
    {
        return array_map(static fn (AttestationRoot $root): string => $root->pem, $roots);
    }

    /**
     * @param list<AdmittedModelUnderAlert> $models
     *
     * @return list<string>
     */
    private static function days(array $models): array
    {
        return array_map(static fn (AdmittedModelUnderAlert $model): string => $model->aaguid->toRfc4122() . ' ' . $model->day(), $models);
    }
}
