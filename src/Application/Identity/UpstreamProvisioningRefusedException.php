<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;

/**
 * An upstream provider named a person this provider does not create or write (decision 0029). Raised before
 * anything is written, or once what was written is taken back, so a refusal creates nobody and changes
 * nobody.
 *
 * **One message, whatever the reason.** The reason is for the audit trail; the message names neither the
 * address, which is a personal datum and goes wherever an exception is logged, nor the reason, which would
 * tell whoever reads it whether somebody of the tenant holds that address.
 */
final class UpstreamProvisioningRefusedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly UpstreamProvisioningRefusalReason $reason,
    ) {
        parent::__construct('The person an upstream provider named is not one this provider creates or writes.');
    }
}
