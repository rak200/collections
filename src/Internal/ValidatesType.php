<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use InvalidArgumentException;
use function sprintf;
use function get_debug_type;

/**
 * Shared type check for collections that hold a `$type` discriminator
 * (a class-string or `'mixed'`). When `$type` is not `'mixed'`, every value
 * passed to `checkType()` must be an instance of that class.
 *
 * The using class must declare a `string $type` property accessible to the
 * trait — `protected` (as in {@see \Rak200\Collections\AbstractCollection})
 * or `private` (as in {@see \Rak200\Collections\LinkedList},
 * {@see \Rak200\Collections\Queue}, {@see \Rak200\Collections\PriorityQueue}).
 * Both work because traits resolve member access in the using class's scope.
 *
 * @internal Not part of the public API; subject to change.
 */
trait ValidatesType {

    /**
     * @throws InvalidArgumentException When $item is not an instance of $this->type.
     */
    protected function checkType(mixed $item): void {
        if ($this->type === 'mixed') {
            return;
        }
        if (!($item instanceof $this->type)) {
            throw new InvalidArgumentException(sprintf(
                'Item must be an instance of %s. Got: %s',
                $this->type,
                get_debug_type($item)
            ));
        }
    }
}
