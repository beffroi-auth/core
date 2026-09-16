<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Stringable;

/**
 * An Authentication Method Reference, the vocabulary of the "amr" claim (RFC 8176 §2). The registered values
 * are constants; any other well-formed value is accepted, because the RFC lets providers define their own
 * and a federated login carries whatever the upstream provider sent. Same choice as Symfony 8.2, whose
 * security token records its proofs under these strings (symfony/symfony#66064).
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
     * What each registered value proves (RFC 8176 §2): knowledge, possession or inherence. A password, a
     * PIN and knowledge-based questions are knowledge; a one-time password, a key, a smart card, a phone
     * reached by SMS or by voice are possession; a face, a fingerprint, an iris, a retina, a voice are
     * inherence.
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
     * The kind of factor this method proves, or null when it proves none by itself: "mfa" says several
     * were used without naming them, "user" is a presence check, "geo", "rba", "mca" and "wia" describe
     * the context of a proof rather than a proof, and a value the registry does not know says nothing.
     */
    public function factor(): ?AuthenticationFactor
    {
        return self::FACTORS[$this->value] ?? null;
    }

    /**
     * RFC 8176 §2 "mfa": an upstream provider asserted multiple factors without naming each of them.
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
