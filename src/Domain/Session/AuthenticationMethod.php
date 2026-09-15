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

    public static function password(): self
    {
        return new self(self::PASSWORD);
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
