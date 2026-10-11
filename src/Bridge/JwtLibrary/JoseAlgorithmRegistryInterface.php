<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Exception\UnsupportedAlgorithmException;

/**
 * One registry of algorithms as a reader of several reads it (JoseKeyAlgorithms): what it holds, the
 * lookup that answers nothing rather than refusing, and the refusal it words for a name it does not carry.
 * The two are apart because a reader of several needs both: a name the next registry carries costs no
 * exception, and a name none of them carries is refused with what each of them had to say about it.
 */
interface JoseAlgorithmRegistryInterface
{
    /**
     * @return list<JoseKeyAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    /**
     * The algorithm wired under that name, null when this registry holds none.
     */
    public function find(string $name): ?JoseKeyAlgorithmInterface;

    /**
     * What this registry has to say of a name it does not carry, in its own words: what the provider signs
     * with, is encrypted to with, hands out or seals a message body to, which no other registry can say.
     */
    public function refusalOf(string $name): UnsupportedAlgorithmException;
}
