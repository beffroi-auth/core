<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Confirmation;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * A kind of address a confirmation establishes.
 */
interface ConfirmableAddressInterface
{
    /**
     * OIDC Core §5.1, the claim of the address, which names its verification "<claim>_verified" too.
     */
    public function claim(): string;

    public function of(User $user): ?string;

    public function isVerified(User $user): bool;

    public function establish(User $user, DateTimeImmutable $now): void;

    /**
     * The confirmation of this kind, valid for as long as NIST SP 800-63B-4 §4.2.1.2 lets a code sent there be.
     */
    public function draw(Uuid $id, User $user, PasswordHash $code, PasswordHash $link, DateTimeImmutable $now): AddressConfirmation;
}
