<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use Beffroi\Core\Domain\Encoding\PlainText;
use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_check_encoding;
use function mb_strlen;
use Normalizer;
use function normalizer_is_normalized;
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

final class Group implements ClassifiedFieldsInterface
{
    public const int NAME_MAX_LENGTH = 255;

    /**
     * Unicode White_Space: what \s matches in ASCII, the separators of category Z, and NEXT LINE.
     */
    private const string EDGE_WHITESPACE = '/^[\s\p{Z}\x{85}]|[\s\p{Z}\x{85}]\z/u';

    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public private(set) string $name,
        public readonly GroupSource $source,
        public readonly DateTimeImmutable $createdAt,
    ) {
        $this->rename($name);
    }

    /**
     * Held to NFC with no edge whitespace: the unique constraint and an upstream mapping compare names exactly.
     *
     * @throws InvalidArgumentException when the name is empty, too long, not in NFC, begins or ends with whitespace, or is
     *                                  not plain text
     */
    public function rename(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('A group name must not be empty.');
        }
        if (! mb_check_encoding($name, 'UTF-8') || ! normalizer_is_normalized($name, Normalizer::FORM_C)) {
            throw new InvalidArgumentException('A group name must be UTF-8 in Unicode Normalization Form C.');
        }
        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('A group name must not be longer than %d characters.', self::NAME_MAX_LENGTH));
        }
        if (preg_match(self::EDGE_WHITESPACE, $name) !== 0) {
            throw new InvalidArgumentException('A group name must not begin or end with whitespace.');
        }
        if (! PlainText::holds($name, self::NAME_MAX_LENGTH)) {
            throw new InvalidArgumentException('A group name must be plain text, with no control, format or line separator character.');
        }
        $this->name = $name;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'name' => new NoPersonalDatum('names a group of the tenant, an organisational unit and not a person'),
            'source' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
        ];
    }
}
