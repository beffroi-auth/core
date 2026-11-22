<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Confirmation;

use Beffroi\Core\Application\Authentication\DecoyHash;
use Beffroi\Core\Domain\Confirmation\AddressConfirmation;
use Beffroi\Core\Domain\Confirmation\ConfirmableAddressInterface;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\AddressConfirmationRefusedException;
use Beffroi\Core\Port\AddressConfirmationRepositoryInterface;
use Beffroi\Core\Port\CodeAttemptLimiterInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use function array_key_last;
use function preg_replace;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * NIST SP 800-63B-4 §4.2.1.2, §3.2.2, decisions 0014 and 0026
 *
 * A code or a link given back by the person it was sent for: every refusal costs a hash and says the same sentence,
 * and the hash binds each to the address the person holds now, so that one sent to another address establishes
 * nothing.
 */
final readonly class AddressConfirmationRedeeming
{
    public const string REFUSED = 'This confirmation establishes nothing.';

    public function __construct(
        private PasswordHasherInterface $hasher,
        private DecoyHash $decoy,
        private CodeAttemptLimiterInterface $attempts,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AddressConfirmationRefusedException
     */
    public function byCode(
        AddressConfirmationRepositoryInterface $confirmations,
        ConfirmableAddressInterface $kind,
        User $user,
        #[SensitiveParameter]
        string $typed,
    ): AddressConfirmation {
        $unspent = $confirmations->unspentOf($user);
        $confirmation = $unspent === [] ? null : $unspent[array_key_last($unspent)];

        return $this->verified($confirmations, $user, $confirmation, $confirmation?->code, $kind->of($user), (string) preg_replace('/\s+/', '', $typed));
    }

    /**
     * @throws AddressConfirmationRefusedException
     */
    public function byLink(
        AddressConfirmationRepositoryInterface $confirmations,
        ConfirmableAddressInterface $kind,
        User $user,
        Uuid $id,
        #[SensitiveParameter]
        string $value,
    ): AddressConfirmation {
        $found = $confirmations->find($id);
        // Another person's confirmation is not theirs to wear out with wrong values.
        $confirmation = $found?->isFor($user) === true ? $found : null;

        return $this->verified($confirmations, $user, $confirmation, $confirmation?->link, $kind->of($user), $value);
    }

    /**
     * Counted under the limit of a guess before anything is verified, and a wrong secret counts against the
     * confirmation it was given for.
     *
     * @throws AddressConfirmationRefusedException
     */
    private function verified(
        AddressConfirmationRepositoryInterface $confirmations,
        User $user,
        ?AddressConfirmation $confirmation,
        ?PasswordHash $secret,
        ?string $address,
        #[SensitiveParameter]
        string $presented,
    ): AddressConfirmation {
        if (! $this->attempts->attempt($user) || $confirmation === null || $secret === null || $address === null) {
            $this->decoy->verify($presented);
            throw new AddressConfirmationRefusedException(self::REFUSED);
        }
        if (! $this->hasher->verify($secret, AddressConfirmation::bound($presented, $address))) {
            $confirmation->failed();
            $confirmations->save($confirmation);
            throw new AddressConfirmationRefusedException(self::REFUSED);
        }
        // After the secret on purpose: a confirmation spent, out of time or worn out answers like a wrong one.
        if (! $confirmation->opens($this->clock->now())) {
            throw new AddressConfirmationRefusedException(self::REFUSED);
        }

        return $confirmation;
    }
}
