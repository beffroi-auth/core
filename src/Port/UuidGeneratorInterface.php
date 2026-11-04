<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Symfony\Component\Uid\Uuid;

interface UuidGeneratorInterface
{
    /**
     * RFC 9562 §5.7
     */
    public function timeOrdered(): Uuid;

    /**
     * RFC 9562 §5.4
     *
     * For a value a party sees, a "sub" above all, which must not date the account it names.
     */
    public function opaque(): Uuid;
}
