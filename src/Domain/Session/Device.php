<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_keys;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function str_contains;

final readonly class Device
{
    /**
     * Most particular first: every browser built on Chromium also says "Chrome".
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
     * Most particular first: Android also says "Linux", and an iPhone "like Mac OS X".
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

    private const string PROGRAM_PATTERN = '/^([A-Za-z][A-Za-z.+-]*)\/([0-9][0-9A-Za-z.-]*)/D';

    public function __construct(
        public DeviceType $type = DeviceType::Unknown,
        public ?string $browser = null,
        public ?string $browserVersion = null,
        public ?string $platform = null,
        public ?string $platformVersion = null,
    ) {
    }

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
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException
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
