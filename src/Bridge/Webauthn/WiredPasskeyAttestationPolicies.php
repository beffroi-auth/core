<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Domain\Credential\Attestation\PasskeyAttestationPolicyInterface;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\PasskeyAttestationPoliciesInterface;
use function implode;
use function sprintf;

final readonly class WiredPasskeyAttestationPolicies implements PasskeyAttestationPoliciesInterface
{
    /**
     * @var WiredAlgorithms<PasskeyAttestationPolicyInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<PasskeyAttestationPolicyInterface> $policies
     *
     * @throws InvalidArgumentException when a name is wired twice
     */
    public function __construct(iterable $policies)
    {
        $this->wired = new WiredAlgorithms($policies);
    }

    public function named(string $name): PasskeyAttestationPolicyInterface
    {
        return (
            $this->wired->named($name) ?? throw new InvalidArgumentException(sprintf(
                'This provider wires no passkey attestation policy "%s"; it wires %s.',
                $name,
                $this->wired->algorithms === [] ? 'none' : implode(', ', $this->wired->names()),
            ))
        );
    }
}
