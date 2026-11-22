<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Notification;

use function array_is_list;
use function array_key_exists;
use function array_unique;
use function array_values;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sort;
use function sprintf;

/**
 * The channels a person chose to be told by: the ones of a kind they set apart, else the ones they chose for every
 * kind, else every channel the deployment turns on. A kind no choice narrows reaches them by every channel.
 */
final readonly class ChosenChannels
{
    /**
     * The name the history of the person writes the choice for every kind under, and the prefix of the name it writes
     * the choice of a kind set apart under.
     */
    public const string HISTORY = 'notification_channels';

    /**
     * @var list<string>|null in the order of their names, null when the person chose nothing for every kind
     */
    public ?array $forEveryKind;

    /**
     * @var array<string, list<string>> the channels of each kind set apart, by the name of the kind
     */
    public array $setApart;

    /**
     * @param list<string>|null            $forEveryKind
     * @param array<string, list<string>> $setApart     by the name of the kind
     *
     * @throws InvalidArgumentException when a channel or a kind is not a name
     */
    public function __construct(?array $forEveryKind = null, array $setApart = [])
    {
        $this->forEveryKind = $forEveryKind === null ? null : self::channels($forEveryKind);
        $apart = [];
        foreach ($setApart as $kind => $channels) {
            $apart[self::name($kind, 'kind')] = self::channels($channels);
        }
        $this->setApart = $apart;
    }

    public static function none(): self
    {
        return new self();
    }

    public function isNone(): bool
    {
        return $this->forEveryKind === null && $this->setApart === [];
    }

    public function reaches(NotificationKindInterface $kind, string $channel): bool
    {
        if (! $kind instanceof ChoosableNotificationKindInterface) {
            return true;
        }
        $chosen = $this->setApart[$kind->name()] ?? $this->forEveryKind;

        return $chosen === null || in_array($channel, $chosen, true);
    }

    /**
     * The choice as the history of the person writes it: the channels of every kind under `notification_channels`, those
     * of each kind set apart under `notification_channels.<kind>`, separated by a space, and nothing for what was not
     * chosen.
     *
     * @return array<string, string>
     */
    public function history(): array
    {
        $history = [];
        if ($this->forEveryKind !== null) {
            $history[self::HISTORY] = implode(' ', $this->forEveryKind);
        }
        foreach ($this->setApart as $kind => $channels) {
            $history[self::HISTORY . '.' . $kind] = implode(' ', $channels);
        }

        return $history;
    }

    /**
     * @return array{every_kind?: list<string>, set_apart?: array<string, list<string>>}|null
     */
    public function toDocument(): ?array
    {
        if ($this->isNone()) {
            return null;
        }
        $document = [];
        if ($this->forEveryKind !== null) {
            $document['every_kind'] = $this->forEveryKind;
        }
        if ($this->setApart !== []) {
            $document['set_apart'] = $this->setApart;
        }

        return $document;
    }

    /**
     * @param array<array-key, mixed>|null $document
     *
     * @throws InvalidArgumentException
     */
    public static function fromDocument(?array $document): self
    {
        if ($document === null) {
            return self::none();
        }
        $read = new Document($document);
        $forEveryKind = array_key_exists('every_kind', $document) ? self::read($document['every_kind']) : null;
        $setApart = [];
        foreach ($read->nested('set_apart') ?? [] as $kind => $channels) {
            $setApart[(string) $kind] = self::read($channels);
        }

        return new self($forEveryKind, $setApart);
    }

    /**
     * @param list<string> $channels
     *
     * @throws InvalidArgumentException
     *
     * @return list<string>
     */
    private static function channels(array $channels): array
    {
        $named = [];
        foreach ($channels as $channel) {
            $named[] = self::name($channel, 'channel');
        }
        $named = array_values(array_unique($named));
        sort($named);

        return $named;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return list<string>
     */
    private static function read(mixed $entries): array
    {
        if (! is_array($entries) || ! array_is_list($entries)) {
            throw new InvalidArgumentException('The channels a person chose are a JSON list.');
        }
        $channels = [];
        foreach ($entries as $entry) {
            if (! is_string($entry)) {
                throw new InvalidArgumentException('A channel a person chose is named by a string.');
            }
            $channels[] = $entry;
        }

        return $channels;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function name(string $name, string $what): string
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('A %s is named by lowercase letters, digits and underscores, not "%s".', $what, $name));
        }

        return $name;
    }
}
