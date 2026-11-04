<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

interface OpenedSigningKeyInterface
{
    /**
     * RFC 7515 §7.1
     *
     * @param array<string, mixed> $protectedHeader the members besides "alg" and "kid", which are the key's
     */
    public function sign(string $payload, array $protectedHeader = []): string;
}
