<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Attestation\PasskeyAttestationPolicyInterface;
use Beffroi\Core\Exception\InvalidArgumentException;

interface PasskeyAttestationPoliciesInterface
{
    /**
     * @throws InvalidArgumentException naming the policies that are wired
     */
    public function named(string $name): PasskeyAttestationPolicyInterface;
}
