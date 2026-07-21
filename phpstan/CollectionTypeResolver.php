<?php

declare(strict_types=1);

namespace Rak200\Collections\PHPStan;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Rak200\Collections\BiMap;
use Rak200\Collections\CircularBuffer;
use Rak200\Collections\Deque;
use Rak200\Collections\ImmutableMap;
use Rak200\Collections\ImmutableSet;
use Rak200\Collections\LinkedList;
use Rak200\Collections\Map;
use Rak200\Collections\MultiMap;
use Rak200\Collections\MultiSet;
use Rak200\Collections\ObjectMap;
use Rak200\Collections\OrderedSet;
use Rak200\Collections\PriorityQueue;
use Rak200\Collections\Queue;
use Rak200\Collections\Set;
use Rak200\Collections\Stack;
use Rak200\Collections\Vector;

use function array_key_exists;
use function class_exists;
use function interface_exists;

/**
 * Binds each collection's `@template` value/key parameters from the
 * discriminator string(s) passed to its constructor, so class-strings,
 * `'mixed'`, `'object'`, scalars and the built-in pseudo-types
 * (`'int'`, `'string'`, `'bool'`, `'float'`, `'array'`, `'iterable'`,
 * `'callable'`) all yield a correctly parameterised `new` expression type.
 *
 * PHPStan cannot infer this natively because the constructor argument is a
 * plain runtime string, not a `class-string<T>`; without this extension a
 * `new Vector('int')` resolves to an unbound template and every subsequent
 * call is reported as a false positive.
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class CollectionTypeResolver implements ExpressionTypeResolverExtension
{
    /**
     * Per-class constructor slots, in `@template` declaration order.
     * Each slot: `[argument index, default discriminator, is-key flag]`.
     *
     * @var array<class-string, list<array{int, string, bool}>>
     */
    private const array SPEC = [
        Vector::class => [[0, 'mixed', false]],
        Set::class => [[0, 'mixed', false]],
        OrderedSet::class => [[0, 'mixed', false]],
        Stack::class => [[0, 'mixed', false]],
        Queue::class => [[0, 'mixed', false]],
        LinkedList::class => [[0, 'mixed', false]],
        MultiSet::class => [[0, 'mixed', false]],
        Deque::class => [[0, 'mixed', false]],
        PriorityQueue::class => [[0, 'mixed', false]],
        ImmutableSet::class => [[0, 'mixed', false]],
        CircularBuffer::class => [[1, 'mixed', false]],
        Map::class => [[0, 'mixed', true], [1, 'mixed', false]],
        ImmutableMap::class => [[0, 'mixed', true], [1, 'mixed', false]],
        MultiMap::class => [[0, 'mixed', true], [1, 'mixed', false]],
        BiMap::class => [[0, 'mixed', true], [1, 'mixed', false]],
        ObjectMap::class => [[0, 'object', true], [1, 'object', false]],
    ];

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (!$expr instanceof New_ || !$expr->class instanceof Name) {
            return null;
        }
        $class = $expr->class->toString();
        if ($class === 'self') {
            // `new self(...)` inside the collections' own factory methods.
            $class = $scope->getClassReflection()?->getName();
            if ($class === null) {
                return null;
            }
        }
        if (!array_key_exists($class, self::SPEC)) {
            return null;
        }

        $args = $expr->getArgs();
        $generics = [];
        foreach (self::SPEC[$class] as [$index, $default, $isKey]) {
            if (!isset($args[$index])) {
                $generics[] = $this->mapType($default, $isKey);

                continue;
            }
            $argType = $scope->getType($args[$index]->value);
            $constantStrings = $argType->getConstantStrings();
            if ($constantStrings !== []) {
                // One literal binds directly; a literal union binds to the
                // union of the mapped types (e.g. 'int'|'string' keys).
                $types = [];
                foreach ($constantStrings as $constantString) {
                    $types[] = $this->mapType($constantString->getValue(), $isKey);
                }
                $generics[] = TypeCombinator::union(...$types);

                continue;
            }
            if ($argType->isClassString()->yes()) {
                // class-string<T> from a typed factory: bind T itself, so
                // `new self($valueClass)` inside `of()` stays generic.
                $generics[] = $argType->getClassStringObjectType();

                continue;
            }

            // Non-constant discriminator: can't parameterise safely.
            return null;
        }

        return new GenericObjectType($class, $generics);
    }

    /**
     * Translate a discriminator string into the matching PHPStan type.
     * Keys clamp `'mixed'` to `int|string` since PHP array keys can only be
     * `int|string`.
     */
    private function mapType(string $discriminator, bool $isKey): Type
    {
        return match ($discriminator) {
            'mixed' => $isKey
                ? TypeCombinator::union(new IntegerType(), new StringType())
                : new MixedType(),
            'object' => new ObjectWithoutClassType(),
            'int', 'integer' => new IntegerType(),
            'string' => new StringType(),
            'bool', 'boolean' => new BooleanType(),
            'float', 'double' => new FloatType(),
            'array' => new ArrayType(new MixedType(), new MixedType()),
            'iterable' => new IterableType(new MixedType(), new MixedType()),
            'callable' => new CallableType(),
            default => class_exists($discriminator) || interface_exists($discriminator)
                ? new ObjectType($discriminator)
                : new MixedType(),
        };
    }
}
