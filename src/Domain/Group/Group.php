<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

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
use function preg_match;
use function sprintf;
use Symfony\Component\Uid\Uuid;

final class Group implements ClassifiedFieldsInterface
{
    /**
     * The length of the column, in characters.
     */
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
     * A name is compared exactly, case included, by the unique constraint of its tenant and by the mapping of
     * an upstream provider, so it is held to one spelling of what it says: in Unicode Normalization Form C,
     * without whitespace at either end, so that "admins" and "admins" followed by a no-break space are not two
     * groups, and no longer than its column.
     *
     * @throws InvalidArgumentException when the name is empty, too long, not in NFC, or begins or ends with whitespace
     */
    public function rename(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('A group name must not be empty.');
        }
        if (! mb_check_encoding($name, 'UTF-8') || ! Normalizer::isNormalized($name, Normalizer::FORM_C)) {
            throw new InvalidArgumentException('A group name must be UTF-8 in Unicode Normalization Form C.');
        }
        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('A group name must not be longer than %d characters.', self::NAME_MAX_LENGTH));
        }
        if (preg_match(self::EDGE_WHITESPACE, $name) !== 0) {
            throw new InvalidArgumentException('A group name must not begin or end with whitespace.');
        }
        $this->name = $name;
    }

    /**
     * A group names an organisational unit of its tenant, and nothing of it says who is in it.
     *
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
