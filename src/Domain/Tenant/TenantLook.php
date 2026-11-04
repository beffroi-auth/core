<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;

/**
 * A colour is three or six hexadecimal digits and nothing else: it is written into a style attribute.
 */
final readonly class TenantLook
{
    /**
     * CSS Color 4 §5.1
     */
    private const string COLOR = '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/Di';

    public function __construct(
        public ?LogoUri $logoUri = null,
        public ?string $primaryColor = null,
        public ?string $primaryContentColor = null,
    ) {
        foreach (['primaryColor' => $primaryColor, 'primaryContentColor' => $primaryContentColor] as $field => $color) {
            if ($color !== null && preg_match(self::COLOR, $color) !== 1) {
                throw new InvalidArgumentException(sprintf(
                    'The %s "%s" of a tenant must be three or six hexadecimal digits behind a hash, as "#1b4b8f".',
                    $field === 'primaryColor' ? 'primary colour' : 'primary content colour',
                    $color,
                ));
            }
        }
        if (($primaryColor === null) !== ($primaryContentColor === null)) {
            throw new InvalidArgumentException('A tenant declares the colour of its pages and the colour of what is written on it together, or neither of them.');
        }
    }

    public static function none(): self
    {
        return new self();
    }

    /**
     * @return array<string, string>
     */
    public function toDocument(): array
    {
        $document = [];
        if ($this->logoUri !== null) {
            $document['logo_uri'] = $this->logoUri->value;
        }
        if ($this->primaryColor !== null) {
            $document['primary_color'] = $this->primaryColor;
        }
        if ($this->primaryContentColor !== null) {
            $document['primary_content_color'] = $this->primaryContentColor;
        }

        return $document;
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException
     */
    public static function fromDocument(array $document): self
    {
        $members = new Document($document);
        $logoUri = $members->nullableString('logo_uri');

        return new self(
            $logoUri === null ? null : new LogoUri($logoUri),
            $members->nullableString('primary_color'),
            $members->nullableString('primary_content_color'),
        );
    }

    public function isDeclared(): bool
    {
        return $this->logoUri !== null || $this->primaryColor !== null;
    }

    public function equals(self $other): bool
    {
        $logo = match (true) {
            $this->logoUri === null => $other->logoUri === null,
            $other->logoUri === null => false,
            default => $this->logoUri->equals($other->logoUri),
        };

        return $logo && $this->primaryColor === $other->primaryColor && $this->primaryContentColor === $other->primaryContentColor;
    }
}
