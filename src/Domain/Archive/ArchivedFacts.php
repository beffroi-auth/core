<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Archive;

use Beffroi\Core\Domain\Session\RequestContext;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * The readings every chapter of an archive shares (issue #453): a moment, and what a request carried of
 * where it came from. A named mapper beside the objects it reads, so that two chapters writing the same
 * fact write it the same way and a reader of the document learns one shape.
 *
 * A moment is written in the shape of RFC 3339, which is what RGPD article 20 means by machine readable.
 * Dates are held in UTC in this schema, so what a person reads here is UTC whatever their own clock says;
 * the pages of their account are where a date is shown in their time zone and their language (issue #469).
 */
final readonly class ArchivedFacts
{
    public static function moment(?DateTimeImmutable $moment): ?string
    {
        return $moment?->format(DateTimeInterface::ATOM);
    }

    /**
     * What the request carried: the address and the user agent as they reached this provider, and what the
     * enrichment made of them when it ran. It is in the archive because it is a personal datum this
     * provider holds, which is the whole question article 15 asks.
     *
     * @return array<string, mixed>
     */
    public static function context(RequestContext $context): array
    {
        return [
            'received_at' => self::moment($context->receivedAt),
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'location' => $context->location === null
                ? null
                : [
                    'country_code' => $context->location->countryCode,
                    'region' => $context->location->region,
                    'city' => $context->location->city,
                    'autonomous_system_number' => $context->location->autonomousSystemNumber,
                    'autonomous_system_organization' => $context->location->autonomousSystemOrganization,
                    'latitude' => $context->location->latitude,
                    'longitude' => $context->location->longitude,
                ],
            'device' => $context->device === null
                ? null
                : [
                    'type' => $context->device->type->value,
                    'browser' => $context->device->browser,
                    'browser_version' => $context->device->browserVersion,
                    'platform' => $context->device->platform,
                    'platform_version' => $context->device->platformVersion,
                ],
        ];
    }
}
