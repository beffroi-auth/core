<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * WebAuthn Level 3 §5.8.4
 */
interface ReachableCredentialInterface
{
    /**
     * @var list<AuthenticatorTransport>
     */
    public array $reachedThrough { get; }
}
