<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Exception\InvalidArgumentException;
use function idn_to_ascii;
use const IDNA_CHECK_BIDI;
use const IDNA_CHECK_CONTEXTJ;
use const IDNA_NONTRANSITIONAL_TO_ASCII;
use const IDNA_USE_STD3_RULES;
use const INTL_IDNA_VARIANT_UTS46;
use function is_array;
use function preg_match;
use function sprintf;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;

/**
 * RFC 1035 §2.3.4, RFC 1123 §2.1, RFC 5890 §2.3.2.1, RFC 4343
 *
 * The only reading of the domain of an address: two readings could disagree on whether it is federated.
 */
final readonly class EmailDomain
{
    /**
     * RFC 1035 §2.3.4, without the final dot
     */
    public const int MAX_LENGTH = 253;

    private const string NAME = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?\z/';

    /**
     * UTS #46 §4
     */
    private const int IDNA = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ;

    public string $value;

    /**
     * Its A-labels must read back through the IDNA as written: otherwise no address could ever be of it.
     *
     * @throws InvalidArgumentException when it is not a DNS name of two labels at least
     */
    public function __construct(string $domain)
    {
        $value = strtolower($domain);
        if (strlen($value) > self::MAX_LENGTH || preg_match(self::NAME, $value) !== 1 || self::ascii($value) !== $value) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is no e-mail domain: a name of the DNS of two labels at least, of letters, digits and hyphens, a name outside ASCII written as its A-label.',
                $domain,
            ));
        }
        $this->value = $value;
    }

    /**
     * A refusal never names the address, which is a person's.
     *
     * @throws InvalidArgumentException
     */
    public static function ofAddress(string $address): self
    {
        $at = strrpos($address, '@');
        $domain = $at === false ? '' : substr($address, $at + 1);
        if ($domain === '') {
            throw new InvalidArgumentException('An address holds a domain after its "@".');
        }
        $ascii = self::ascii($domain);
        if ($ascii === null) {
            throw new InvalidArgumentException('The domain of an address cannot be written as a name of the DNS by the IDNA of UTS #46.');
        }
        try {
            return new self($ascii);
        } catch (InvalidArgumentException $refusal) {
            throw new InvalidArgumentException('The domain of an address is no name of the DNS of two labels at least.', 0, $refusal);
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function ascii(string $domain): ?string
    {
        $ascii = idn_to_ascii($domain, self::IDNA, INTL_IDNA_VARIANT_UTS46, $info);

        return $ascii === false || ! is_array($info) || ($info['errors'] ?? null) !== 0 ? null : $ascii;
    }
}
