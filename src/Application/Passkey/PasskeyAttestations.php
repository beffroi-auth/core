<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Passkey;

use function array_map;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Domain\Tenant\AdmittedModelUnderAlert;
use Beffroi\Core\Domain\Tenant\AttestationRoot;
use Beffroi\Core\Domain\Tenant\PasskeyAttestation;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AttestationRootReaderInterface;
use Beffroi\Core\Port\PasskeyAttestationPoliciesInterface;
use function is_string;

/**
 * The attestation of the passkeys of a tenant as its configuration names it, and as the document it is stored as: a
 * policy by its name on the wired policies, the roots in PEM, the models under alert as a list of an AAGUID and a day.
 */
final readonly class PasskeyAttestations
{
    private const string POLICY = 'policy';

    private const string ROOTS = 'roots';

    private const string OWN_ROOTS = 'own_roots';

    private const string MODELS_UNDER_ALERT = 'models_under_alert';

    private const string AAGUID = 'aaguid';

    private const string UNTIL = 'until';

    public function __construct(
        private PasskeyAttestationPoliciesInterface $policies,
        private AttestationRootReaderInterface $roots,
    ) {
    }

    /**
     * @param array<array-key, mixed> $roots PEM certificates
     * @param array<array-key, mixed> $ownRoots PEM certificates
     * @param array<array-key, mixed> $modelsUnderAlert AAGUID to the last day it is admitted, as "2027-03-31"
     *
     * @throws InvalidArgumentException
     */
    public function named(string $policy, array $roots = [], array $ownRoots = [], array $modelsUnderAlert = []): PasskeyAttestation
    {
        $models = [];
        foreach ($modelsUnderAlert as $aaguid => $until) {
            if (! is_string($until)) {
                throw new InvalidArgumentException('A model under a security alert is admitted until a day, written as "2027-03-31".');
            }
            $models[] = AdmittedModelUnderAlert::fromStrings((string) $aaguid, $until);
        }

        return new PasskeyAttestation($this->policies->named($policy), $this->read($roots), $this->read($ownRoots), $models);
    }

    /**
     * @return array<string, mixed>
     */
    public function document(PasskeyAttestation $attestation): array
    {
        $pems = static fn (AttestationRoot $root): string => $root->pem;

        return [
            self::POLICY => $attestation->policy->name(),
            self::ROOTS => array_map($pems, $attestation->roots),
            self::OWN_ROOTS => array_map($pems, $attestation->ownRoots),
            self::MODELS_UNDER_ALERT => array_map(static fn (AdmittedModelUnderAlert $model): array => [
                self::AAGUID => $model->aaguid->toRfc4122(),
                self::UNTIL => $model->day(),
            ], $attestation->modelsUnderAlert),
        ];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when the document is not one, or names a policy nothing wired carries
     */
    public function of(array $document): PasskeyAttestation
    {
        $members = new Document($document);
        $models = [];
        foreach ($members->entries(self::MODELS_UNDER_ALERT) as $entry) {
            $model = Document::of($entry, 'model under a security alert');
            $models[] = AdmittedModelUnderAlert::fromStrings(
                $model->nullableString(self::AAGUID) ?? throw new InvalidArgumentException('A model under a security alert is named by its AAGUID.'),
                $model->nullableString(self::UNTIL) ?? throw new InvalidArgumentException('A model under a security alert is admitted until a day.'),
            );
        }

        return new PasskeyAttestation(
            $this->policies->named(
                $members->nullableString(self::POLICY) ?? throw new InvalidArgumentException('The passkey attestation of a tenant names its policy.'),
            ),
            $this->read($members->entries(self::ROOTS)),
            $this->read($members->entries(self::OWN_ROOTS)),
            $models,
        );
    }

    /**
     * @param array<array-key, mixed> $pems
     *
     * @throws InvalidArgumentException
     *
     * @return list<AttestationRoot>
     */
    private function read(array $pems): array
    {
        $roots = [];
        foreach ($pems as $pem) {
            if (! is_string($pem)) {
                throw new InvalidArgumentException('A root of passkey attestations is a certificate in PEM.');
            }
            $roots[] = $this->roots->read($pem);
        }

        return $roots;
    }
}
