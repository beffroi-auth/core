<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_key_exists;
use function count;
use Beffroi\Core\Domain\Credential\AuthenticatorLogo;
use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Exception\AuthenticatorListRefusedException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AuthenticatorModelSourceInterface;
use function get_object_vars;
use function is_string;
use function json_decode;
use function preg_match;
use const JSON_THROW_ON_ERROR;
use JsonException;
use function sprintf;
use stdClass;
use Symfony\Component\Uid\Uuid;

/**
 * The list of passkey providers the community keeps (aaguid.json of github.com/passkeydeveloper/passkey-
 * authenticator-aaguids, architecture §3.4): a JSON object whose members are AAGUIDs, each naming a model with
 * a name and, when it has them, a logo for a light page ("icon_light") and one for a dark page ("icon_dark"),
 * the one standing for the other when a model has one. It is held to what a page shows rather than to the
 * schema of that repository alone: a member is an AAGUID, a name is not empty, and a logo is a PNG or an SVG
 * image of a base64 data: URL, where the schema asks for an SVG. A list that is anything else is refused whole,
 * as is an object that names nothing: the README of the repository says that a retired list becomes an empty
 * object. A member of a model the schema does not define is left aside.
 *
 * Nobody signs the list and it carries no licence, so nothing ships it: a deployment downloads it, and what it
 * names is what it is, a name somebody gave an AAGUID, which the FIDO metadata are asked about first.
 */
final readonly class AuthenticatorList implements AuthenticatorModelSourceInterface
{
    /**
     * The name of this source, the repository that keeps the list.
     */
    public const string SOURCE = 'passkey-authenticator-aaguids';

    /**
     * Deep enough for a member a model may gain, an object or a list in it; the list nests no further.
     */
    private const int DEPTH = 16;

    /**
     * An AAGUID as a UUID is written, in either case (RFC 9562 §4), whatever its variant: WebAuthn Level 3 §6
     * makes it 128 bits a maker chooses, and the one of Proton Pass, "ProtonPassProton" in ASCII, carries the
     * variant RFC 9562 §4.1 reserves for backward compatibility with NCS.
     */
    private const string AAGUID = '/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/Di';

    /**
     * @param array<string, AuthenticatorModel> $models by AAGUID, in the canonical form of RFC 9562
     */
    private function __construct(
        private array $models,
    ) {
    }

    /**
     * @throws AuthenticatorListRefusedException when the document is not such a list, or names nothing
     */
    public static function fromJson(string $document): self
    {
        try {
            $list = json_decode($document, false, self::DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AuthenticatorListRefusedException(sprintf('it is not JSON: %s', $exception->getMessage()), previous: $exception);
        }
        if (! $list instanceof stdClass) {
            throw new AuthenticatorListRefusedException('it is not a JSON object');
        }
        $models = [];
        foreach (get_object_vars($list) as $aaguid => $member) {
            $aaguid = (string) $aaguid;
            if (preg_match(self::AAGUID, $aaguid) !== 1) {
                throw new AuthenticatorListRefusedException(sprintf('"%s" is not an AAGUID', $aaguid));
            }
            $models[Uuid::fromString($aaguid)->toRfc4122()] = self::described($aaguid, $member);
        }
        if ($models === []) {
            throw new AuthenticatorListRefusedException('it names no authenticator, which is what a retired list becomes');
        }

        return new self($models);
    }

    public function model(Uuid $aaguid): ?AuthenticatorModel
    {
        return $this->models[$aaguid->toRfc4122()] ?? null;
    }

    /**
     * How many models the list names.
     */
    public function count(): int
    {
        return count($this->models);
    }

    /**
     * @throws AuthenticatorListRefusedException
     */
    private static function described(string $aaguid, mixed $member): AuthenticatorModel
    {
        if (! $member instanceof stdClass) {
            throw new AuthenticatorListRefusedException(sprintf('what it says of %s is not an object', $aaguid));
        }
        $fields = get_object_vars($member);
        $name = $fields['name'] ?? null;
        if (! is_string($name) || $name === '') {
            throw new AuthenticatorListRefusedException(sprintf('%s has no name', $aaguid));
        }
        $light = self::icon($aaguid, $fields, 'icon_light');
        $dark = self::icon($aaguid, $fields, 'icon_dark');
        try {
            $logo = match (true) {
                $light !== null => new AuthenticatorLogo($light, $dark ?? $light),
                $dark !== null => AuthenticatorLogo::single($dark),
                default => null,
            };
        } catch (InvalidArgumentException $exception) {
            throw new AuthenticatorListRefusedException(sprintf('the logo of %s is refused: %s', $aaguid, $exception->getMessage()), previous: $exception);
        }

        return new AuthenticatorModel($name, self::SOURCE, $logo);
    }

    /**
     * @param array<mixed> $fields
     *
     * @throws AuthenticatorListRefusedException
     */
    private static function icon(string $aaguid, array $fields, string $variant): ?string
    {
        if (! array_key_exists($variant, $fields)) {
            return null;
        }
        if (! is_string($fields[$variant])) {
            throw new AuthenticatorListRefusedException(sprintf('the "%s" of %s is not a data: URL', $variant, $aaguid));
        }

        return $fields[$variant];
    }
}
