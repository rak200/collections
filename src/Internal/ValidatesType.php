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
 * The using class must declare a `string $type` property.
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
