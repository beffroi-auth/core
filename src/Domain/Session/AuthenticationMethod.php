<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function preg_match;
use function sprintf;
use Stringable;

/**
 * A value of the "amr" claim (RFC 8176 §2). The registered values are constants; any other well-formed
 * value is accepted, as a federated login carries whatever the upstream provider sent.
 */
final readonly class AuthenticationMethod implements Stringable
{
    public const string PASSWORD = 'pwd';

    public const string ONE_TIME_PASSWORD = 'otp';

    public const string MULTIPLE_FACTORS = 'mfa';

    public const string HARDWARE_KEY = 'hwk';

    public const string SOFTWARE_KEY = 'swk';

    public const string USER_PRESENCE = 'user';

    public const string PIN = 'pin';

    public const string FACE = 'face';

    public const string FINGERPRINT = 'fpt';

    public const string IRIS = 'iris';

    public const string GEOLOCATION = 'geo';

    public const string KNOWLEDGE_BASED = 'kba';

    public const string MULTIPLE_CHANNELS = 'mca';

    public const string PROOF_OF_POSSESSION = 'pop';

    public const string RISK_BASED = 'rba';

    public const string RETINA = 'retina';

    public const string SMART_CARD = 'sc';

    public const string SMS = 'sms';

    public const string TELEPHONE = 'tel';

    public const string VOICE = 'vbm';

    public const string WINDOWS_INTEGRATED = 'wia';

    public function __construct(
        public string $value,
    ) {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a well-formed authentication method reference.', $value));
        }
    }

    /**
     * What each registered value proves (RFC 8176 §2).
     */
    private const array FACTORS = [
        self::PASSWORD => AuthenticationFactor::Knowledge,
        self::PIN => AuthenticationFactor::Knowledge,
        self::KNOWLEDGE_BASED => AuthenticationFactor::Knowledge,
        self::ONE_TIME_PASSWORD => AuthenticationFactor::Possession,
        self::HARDWARE_KEY => AuthenticationFactor::Possession,
        self::SOFTWARE_KEY => AuthenticationFactor::Possession,
        self::SMART_CARD => AuthenticationFactor::Possession,
        self::SMS => AuthenticationFactor::Possession,
        self::TELEPHONE => AuthenticationFactor::Possession,
        self::PROOF_OF_POSSESSION => AuthenticationFactor::Possession,
        self::FACE => AuthenticationFactor::Inherence,
        self::FINGERPRINT => AuthenticationFactor::Inherence,
        self::IRIS => AuthenticationFactor::Inherence,
        self::RETINA => AuthenticationFactor::Inherence,
        self::VOICE => AuthenticationFactor::Inherence,
    ];

    public static function password(): self
    {
        return new self(self::PASSWORD);
    }

    /**
     * The distinct kinds of factor a set of methods proves, in the order the methods come. Two proofs of
     * the same kind are one factor (NIST SP 800-63B §2), and a method that proves none is not counted.
     *
     * @param list<self> $methods
     *
     * @return list<AuthenticationFactor>
     */
    public static function factorsOf(array $methods): array
    {
        $factors = [];
        foreach ($methods as $method) {
            $factor = $method->factor();
            if ($factor !== null && ! in_array($factor, $factors, true)) {
                $factors[] = $factor;
            }
        }

        return $factors;
    }

    /**
     * Null when the method proves no factor by itself ("mfa", "user", the context values, an unknown value).
     */
    public function factor(): ?AuthenticationFactor
    {
        return self::FACTORS[$this->value] ?? null;
    }

    /**
     * RFC 8176 §2 "mfa": multiple factors asserted without naming them.
     */
    public function isMultipleFactors(): bool
    {
        return $this->value === self::MULTIPLE_FACTORS;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
