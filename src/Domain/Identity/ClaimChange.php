<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * The identifier is a UUIDv7: the history of a person sorts chronologically by its primary key.
 */
final class ClaimChange implements ClassifiedFieldsInterface
{
    public readonly Tenant $tenant;

    /**
     * @param Subject|null $author the administrator of the tenant who made the change, by the subject of their account; null when the person made it
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly string $claim,
        public readonly ?string $was,
        public readonly ?string $became,
        public readonly DateTimeImmutable $changedAt,
        public readonly RequestContext $context,
        public readonly ?Subject $author = null,
    ) {
        if ($claim === '') {
            throw new InvalidArgumentException('A change of a claim names the claim.');
        }
        if ($was === null && $became === null) {
            throw new InvalidArgumentException('A change of a claim is a change: a claim that said nothing and says nothing did not change.');
        }
        if ($was === $became) {
            throw new InvalidArgumentException('A change of a claim is a change: the value it had and the value it has are the same.');
        }
        $this->tenant = $user->tenant;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'claim' =>
                new NoPersonalDatum('the name of what changed, a claim of OIDC Core §5.1, the username of the account or the key its mail is encrypted to, and never a value'),
            'author' => new NoPersonalDatum(Reason::THE_SUBJECT),
            'was' => new Sealed('claim_change'),
            'became' => new Sealed('claim_change'),
            'changedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'context' => new Sealed('claim_change'),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
