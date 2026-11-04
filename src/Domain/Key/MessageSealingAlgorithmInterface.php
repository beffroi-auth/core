<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * Seals every message body written to a transport; kept unpublished, or anyone could seal a body the consumer opens.
 * It names itself, not a JOSE algorithm, which would make it the active pair clients encrypt to as well.
 */
interface MessageSealingAlgorithmInterface extends KeyAlgorithmInterface
{
}
