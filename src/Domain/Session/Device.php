<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_keys;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function str_contains;

/**
 * What a user agent string says, coarse enough for a session list ("Firefox on Linux, desktop").
 */
final readonly class Device
{
    /**
     * The needle each browser is recognised by, most particular first: every browser built on Chromium
     * says "Chrome" of itself, and the one that says only that is Chrome.
     *
     * @var array<string, string>
     */
    private const array BROWSERS = [
        'Edg/' => 'Edge',
        'EdgiOS/' => 'Edge',
        'OPR/' => 'Opera',
        'SamsungBrowser/' => 'Samsung Internet',
        'CriOS/' => 'Chrome',
        'FxiOS/' => 'Firefox',
        'Firefox/' => 'Firefox',
        'Chrome/' => 'Chrome',
        'Version/' => 'Safari',
    ];

    /**
     * The needle each system is recognised by, most particular first: a telephone on Android says "Linux"
     * of itself, and an iPhone says "like Mac OS X".
     *
     * @var array<string, array{string, DeviceType}>
     */
    private const array PLATFORMS = [
        'Windows NT' => ['Windows', DeviceType::Desktop],
        'Android' => ['Android', DeviceType::Mobile],
        'iPhone' => ['iOS', DeviceType::Mobile],
        'iPad' => ['iPadOS', DeviceType::Tablet],
        'CrOS' => ['ChromeOS', DeviceType::Desktop],
        'Mac OS X' => ['macOS', DeviceType::Desktop],
        'Macintosh' => ['macOS', DeviceType::Desktop],
        'Linux' => ['Linux', DeviceType::Desktop],
    ];

    /**
     * A program that names itself and the version it is at, which is what the user agent of a tool looks
     * like ("curl/8.18.0"): the name is what it called itself, and nothing says it is a browser.
     */
    private const string PROGRAM_PATTERN = '/^([A-Za-z][A-Za-z.+-]*)\/([0-9][0-9A-Za-z.-]*)/D';

    public function __construct(
        public DeviceType $type = DeviceType::Unknown,
        public ?string $browser = null,
        public ?string $browserVersion = null,
        public ?string $platform = null,
        public ?string $platformVersion = null,
    ) {
    }

    /**
     * What the user agent of a request says, read where a page needs it and not written anywhere
     * (architecture §3.5): the string the browser sent is what a context holds, because that is what was
     * received and the only thing a reading can be checked against. A row whose device was read when it
     * was written carries that one, and this answers for every other.
     *
     * **It names what it recognises and nothing else.** A user agent is a declaration nobody verifies, so
     * a reading that invented a name would be one nobody could tell from a true one: an agent this
     * recognises nothing in is nothing, and the caller then shows the string as it stands.
     */
    public static function readFrom(?string $userAgent): ?self
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }
        $browser = self::browserOf($userAgent);
        $needle = self::needleOf(self::PLATFORMS, $userAgent);
        [$platform, $type] = $needle === null ? [null, DeviceType::Unknown] : self::PLATFORMS[$needle];
        if ($browser === null && $platform === null) {
            return null;
        }

        return new self($type, $browser, null, $platform);
    }

    /**
     * The document a column holds a device as, and the document the provenance of a request nests it in
     * (RequestContext::toDocument()): one encoding, written here, so that a nested document and a column
     * of its own cannot drift apart.
     *
     * @return array<string, mixed>
     */
    public function toDocument(): array
    {
        return [
            'type' => $this->type->value,
            'browser' => $this->browser,
            'browser_version' => $this->browserVersion,
            'platform' => $this->platform,
            'platform_version' => $this->platformVersion,
        ];
    }

    /**
     * A device names its type: the four other members say what was recognised, and the type says what
     * kind of thing it was, which no absence stands for.
     *
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when the document names no type of device, or names one this
     *                                  provider has no kind for
     */
    public static function fromDocument(array $document): self
    {
        $members = new Document($document);
        $type = DeviceType::tryFrom($members->nullableString('type') ?? '');
        if ($type === null) {
            throw new InvalidArgumentException('A device names its type.');
        }

        return new self(
            $type,
            $members->nullableString('browser'),
            $members->nullableString('browser_version'),
            $members->nullableString('platform'),
            $members->nullableString('platform_version'),
        );
    }

    private static function browserOf(string $userAgent): ?string
    {
        $needle = self::needleOf(self::BROWSERS, $userAgent);
        if ($needle !== null) {
            return self::BROWSERS[$needle];
        }

        return preg_match(self::PROGRAM_PATTERN, $userAgent, $named) === 1 ? $named[1] : null;
    }

    /**
     * @param array<string, mixed> $names
     */
    private static function needleOf(array $names, string $userAgent): ?string
    {
        foreach (array_keys($names) as $needle) {
            if (str_contains($userAgent, $needle)) {
                return $needle;
            }
        }

        return null;
    }
}
