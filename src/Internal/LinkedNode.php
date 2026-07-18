<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use Rak200\Collections\LinkedList;

/**
 * Node in a {@see \Rak200\Collections\LinkedList}.
 *
 * @template T_Value
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class LinkedNode {

    /**
     * @param LinkedList<T_Value> $owner
     * @param T_Value $value
     * @param self<T_Value>|null $prev
     * @param self<T_Value>|null $next
     */
    public function __construct(
        public readonly LinkedList $owner,
        public readonly mixed $value,
        /** @internal Maintained by LinkedList; do not write directly. */
        public ?self $prev = null,
        /** @internal Maintained by LinkedList; do not write directly. */
        public ?self $next = null,
    ) {}
}
