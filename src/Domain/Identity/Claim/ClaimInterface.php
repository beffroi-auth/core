<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * A claim of OIDC Core §5.1 a person may say of themselves.
 */
interface ClaimInterface
{
    /**
     * The name OIDC Core §5.1 defines, never that of the field holding the value.
     */
    public function name(): string;

    public function of(User $user): ?string;

    public function field(): ClaimField;

    /**
     * @throws InvalidArgumentException
     */
    public function describe(User $user, ?string $value, DateTimeImmutable $now): void;
}
