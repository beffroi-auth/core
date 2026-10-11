<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Archive;

use Beffroi\Core\Domain\Encoding\Base64Url;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Session\RequestContext;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * The readings every chapter of an archive shares (issue #453): a moment, an act of the journal, and what
 * a request carried of where it came from. A named mapper beside the objects it reads, so that two
 * readings writing the same fact write it the same way and a reader of the document learns one shape. The
 * console writes an act with it too (issue #591), a register attached to an answer being the same document
 * as the chapter of an archive.
 *
 * A moment is written in the shape of RFC 3339, which is what RGPD article 20 means by machine readable.
 * Dates are held in UTC in this schema, so what a person reads here is UTC whatever their own clock says;
 * the pages of their account are where a date is shown in their time zone and their language (issue #469).
 *
 * A document is JSON, so a fact that is not text passes through here on its way in: a chapter never hands
 * raw octets to the encoder, which is the one way a chapter can leave a person with no document at all.
 */
final readonly class ArchivedFacts
{
    public static function moment(?DateTimeImmutable $moment): ?string
    {
        return $moment?->format(DateTimeInterface::ATOM);
    }

    /**
     * One act of the journal: what happened, when, on behalf of which application, and what the request
     * carried of where it came from. Never the subject, because whose journal it is is said once, by the
     * document or by the reading that asked for it.
     *
     * @return array<string, mixed>
     */
    public static function act(JournalEntry $act): array
    {
        return [
            'act' => $act->act,
            'occurred_at' => self::moment($act->occurredAt),
            'client' => $act->client,
            'from' => self::context($act->context),
        ];
    }

    /**
     * Raw octets a fact holds (a credential ID as a WebAuthn authenticator issued it), base64url, which is
     * the alphabet the column holding them uses: what the row says and what the archive says of it are one
     * text, and a reader comparing the two compares the same thing.
     */
    public static function octets(?string $octets): ?string
    {
        return $octets === null ? null : Base64Url::encode($octets);
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
