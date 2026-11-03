<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\AuthenticatorModel;
use Beffroi\Core\Exception\RuntimeException;
use Symfony\Component\Uid\Uuid;

/**
 * A source that names models of authenticator by the AAGUID they declare (WebAuthn Level 3 §6, architecture
 * §3.4): the FIDO metadata the console verified, the list of the community the console stored. A source answers
 * for what it holds and for nothing else; the order in which they are asked is the wiring's
 * (AuthenticatorModels).
 */
interface AuthenticatorModelSourceInterface
{
    /**
     * The model this source names for that AAGUID, or nothing when it names none.
     *
     * @throws RuntimeException when what the source holds cannot be read: a defect of its store, not a model it
     *                          does not know
     */
    public function model(Uuid $aaguid): ?AuthenticatorModel;
}
