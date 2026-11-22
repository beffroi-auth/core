<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Confirmation;

use Beffroi\Core\Domain\Confirmation\AddressConfirmation;
use Beffroi\Core\Domain\Confirmation\ConfirmableAddressInterface;
use Beffroi\Core\Domain\Confirmation\IssuedAddressConfirmation;
use Beffroi\Core\Domain\Encoding\Base64Url;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\AddressConfirmationRepositoryInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\RandomnessInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;
use function str_pad;
use const STR_PAD_LEFT;
use function unpack;

/**
 * NIST SP 800-63B-4 §4.2.1.2, §3.2.12, decision 0026
 *
 * The confirmations of the kind the person had not spent go, and a code and a link value are drawn, each kept as a
 * hash bound to the address.
 */
final readonly class AddressConfirmationIssuing
{
    public const int DIGITS = 6;

    public const int LENGTH = 32;

    private const int CODES = 10 ** self::DIGITS;

    /**
     * The largest multiple of CODES that four bytes reach: a draw at or above it is drawn again, so that no code is
     * likelier than another.
     */
    private const int UNBIASED = 4_294_000_000;

    public function __construct(
        private UuidGeneratorInterface $uuids,
        private RandomnessInterface $randomness,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    public function issue(
        AddressConfirmationRepositoryInterface $confirmations,
        ConfirmableAddressInterface $kind,
        User $user,
        string $address,
    ): IssuedAddressConfirmation {
        foreach ($confirmations->unspentOf($user) as $unspent) {
            $confirmations->forget($unspent);
        }
        $code = $this->code();
        $value = Base64Url::encode($this->randomness->bytes(self::LENGTH));
        $confirmation = $kind->draw(
            $this->uuids->timeOrdered(),
            $user,
            $this->hasher->hash(AddressConfirmation::bound($code, $address)),
            $this->hasher->hash(AddressConfirmation::bound($value, $address)),
            $this->clock->now(),
        );
        $confirmations->save($confirmation);

        return new IssuedAddressConfirmation($confirmation, $kind->claim(), $address, $code, $value);
    }

    private function code(): string
    {
        do {
            /** @var array{1: int} $drawn */
            $drawn = unpack('N', $this->randomness->bytes(4));
        } while ($drawn[1] >= self::UNBIASED);

        return str_pad((string) ($drawn[1] % self::CODES), self::DIGITS, '0', STR_PAD_LEFT);
    }
}
