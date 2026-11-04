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
 * The aaguid.json of github.com/passkeydeveloper/passkey-authenticator-aaguids, held to what a page shows
 * rather than to its schema alone. An object that names nothing is refused: the README of that repository
 * says a retired list becomes one.
 */
final readonly class AuthenticatorList implements AuthenticatorModelSourceInterface
{
    public const string SOURCE = 'passkey-authenticator-aaguids';

    private const int DEPTH = 16;

    /**
     * RFC 9562 §4, whatever the variant: WebAuthn Level 3 §6 lets a maker choose all 128 bits, and Proton Pass
     * carries the variant RFC 9562 §4.1 reserves for NCS.
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
