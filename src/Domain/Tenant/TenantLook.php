<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;

/**
 * What a tenant gives the pages of its provider besides its name (issue #449): a logo and the two colours
 * that go together, the one its buttons and its links are drawn in and the one what is written on them is
 * drawn in.
 *
 * **Two colours and not one**, because a colour alone cannot be applied: a pale accent with white on it
 * is unreadable, and whoever gives the first has to answer for the second. Either both or neither.
 *
 * **A colour is six or three hexadecimal digits and nothing else.** It is handed to a browser inside a
 * style attribute, so anything a tenant could write there is what a page would carry: a named colour, a
 * function, a variable and a semicolon are all refused rather than escaped (decision 0006). What a look
 * cannot express is a look nobody has asked for yet.
 *
 * A tenant that declares none of it is "nothing" and renders the pages exactly as a deployment that never
 * heard of a look: this object says what is absent, it never fills it in.
 */
final readonly class TenantLook
{
    /**
     * CSS Color 4 §5.1: three or six hexadecimal digits, with their alpha forms left out, a transparent
     * accent being a mistake and not a brand.
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

    /**
     * The look of a tenant that declared none: what every deployment has been served so far.
     */
    public static function none(): self
    {
        return new self();
    }

    /**
     * The document a column holds a look as: the URL of the logo and the two colours, each absent from it
     * when the tenant declared none, so that what a tenant declared is what the column holds and not
     * three nulls.
     *
     * A tenant that declared nothing is therefore an empty document, which PHP writes as "[]" where the
     * default of the column is "{}"; both are read back as the same look, and nothing queries a value of
     * that column.
     *
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
     * What a look is given back is what refuses a row somebody edited by hand: a colour that is not
     * hexadecimal and a logo that is not an absolute HTTPS URL are refused here as they are refused at
     * the configuration.
     *
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when a member of the document is not a string, or is not what a
     *                                 look accepts
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

    /**
     * Whether anything here reaches a page at all, which is what tells a template to render the brand of
     * a tenant or nothing in its place.
     */
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
