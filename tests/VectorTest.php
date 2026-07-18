<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Vector;

final class VectorTest extends TestCase {

    use ConstructsProtected;

    public function testEmptyConstruction(): void {
        $v = Vector::any();
        self::assertSame('mixed', $v->getType());
        self::assertCount(0, $v);
        self::assertSame([], $v->toArray());
    }

    public function testConstructionWithMixedAcceptsScalarsAndObjects(): void {
        $v = Vector::any([1, 'two', 3.14, new \stdClass()]);
        self::assertCount(4, $v);
    }

    public function testConstructionWithClassTypeAcceptsInstances(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $v = Vector::of(\stdClass::class, [$a, $b]);
        self::assertCount(2, $v);
        self::assertSame($a, $v->get(0));
    }

    public function testConstructionRejectsNonInstance(): void {
        $this->expectException(InvalidArgumentException::class);
        Vector::of(\stdClass::class, ['not-an-object']); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testAddAndGet(): void {
        $v = Vector::any();
        $v->add(0, 'a');
        $v->add(1, 'b');
        self::assertSame('a', $v->get(0));
        self::assertSame('b', $v->get(1));
    }

    public function testGetReturnsNullForMissingKey(): void {
        $v = Vector::any();
        self::assertNull($v->get(99));
    }

    public function testRemove(): void {
        $v = Vector::any([0 => 'a', 1 => 'b']);
        $v->remove(0);
        self::assertNull($v->get(0));
        self::assertSame('b', $v->get(1));
        self::assertCount(1, $v);
    }

    public function testAddRejectsBadType(): void {
        $v = Vector::of(\stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $v->add(0, 'not-an-object'); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testArrayAccessOffsetSetWithExplicitOffset(): void {
        $v = Vector::any();
        $v[5] = 'hello';
        self::assertSame('hello', $v[5]);
        self::assertTrue(isset($v[5]));
    }

    public function testArrayAccessOffsetSetWithNullAppends(): void {
        $v = Vector::any();
        $v[] = 'a';
        $v[] = 'b';
        self::assertSame('a', $v[0]);
        self::assertSame('b', $v[1]);
    }

    public function testArrayAccessOffsetGetReturnsNullForMissing(): void {
        $v = Vector::any();
        self::assertNull($v[42]);
    }

    public function testArrayAccessOffsetExistsAndUnset(): void {
        $v = Vector::any([0 => 'a', 1 => 'b']);
        self::assertTrue(isset($v[0]));
        unset($v[0]);
        self::assertFalse(isset($v[0]));
    }

    public function testArrayAccessOffsetSetRejectsBadType(): void {
        $v = Vector::of(\stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $v[0] = 'scalar'; // @phpstan-ignore offsetAssign.valueType (runtime rejection test)
    }

    public function testIterationPreservesKeysAndOrder(): void {
        $v = Vector::any([10 => 'a', 20 => 'b', 30 => 'c']);
        $out = [];
        foreach ($v as $k => $val) {
            $out[$k] = $val;
        }
        self::assertSame([10 => 'a', 20 => 'b', 30 => 'c'], $out);
    }

    public function testToArrayReturnsUnderlyingArray(): void {
        $v = Vector::any([1, 2, 3]);
        self::assertSame([1, 2, 3], $v->toArray());
    }

    public function testIsAbstractCollection(): void {
        self::assertInstanceOf(AbstractCollection::class, Vector::any());
        self::assertInstanceOf(\ArrayAccess::class, Vector::any());
    }

    /**
     * A2: Vector docs claim int-indexed, but the constructor silently accepts
     * string keys via $items. This is the gap that should have closed when
     * Collection was deprecated. If this test passes, A2 is fixed.
     */
    public function testConstructorRejectsStringKeys(): void {
        $this->expectException(InvalidArgumentException::class);
        Vector::any(['foo' => 'bar']);
    }

    public function testIntTypeAcceptsAndRejects(): void {
        $v = Vector::ofInt([1, 2, 3]);
        self::assertSame([1, 2, 3], $v->toArray());

        $this->expectException(InvalidArgumentException::class);
        Vector::ofInt(['not-int']);
    }

    public function testStringTypeAcceptsAndRejects(): void {
        $v = Vector::ofString(['a', 'b']);
        self::assertSame(['a', 'b'], $v->toArray());

        $this->expectException(InvalidArgumentException::class);
        Vector::ofString([1]);
    }

    public function testBoolTypeAcceptsAndRejects(): void {
        $v = Vector::ofBool([true, false]);
        self::assertSame([true, false], $v->toArray());

        $this->expectException(InvalidArgumentException::class);
        Vector::ofBool([1]);
    }

    public function testFloatTypeAcceptsAndRejects(): void {
        $v = Vector::ofFloat([1.5, 2.5]);
        self::assertSame([1.5, 2.5], $v->toArray());

        $this->expectException(InvalidArgumentException::class);
        Vector::ofFloat([1]);
    }

    public function testArrayTypeAcceptsAndRejects(): void {
        $v = self::build(Vector::class, 'array', [[1], [2]]);
        self::assertSame([[1], [2]], $v->toArray());

        $this->expectException(InvalidArgumentException::class);
        self::build(Vector::class, 'array', [1]);
    }

    public function testIterableTypeAcceptsAndRejects(): void {
        $v = self::build(Vector::class, 'iterable', [[1, 2], new \ArrayObject([3])]);
        self::assertCount(2, $v);

        $this->expectException(InvalidArgumentException::class);
        self::build(Vector::class, 'iterable', [1]);
    }

    public function testCallableTypeAcceptsAndRejects(): void {
        $v = Vector::ofCallable([fn() => 1, 'strlen']);
        self::assertCount(2, $v);

        $this->expectException(InvalidArgumentException::class);
        Vector::ofCallable(['__no_fn__']);
    }

    /**
     * Pseudo-type discriminators on an initially empty vector: each valid
     * item must be accepted by add(), then the wrong item must be rejected.
     *
     * @return iterable<string, array{string, list<mixed>, mixed}>
     */
    public static function pseudoTypeAddProvider(): iterable {
        yield 'int' => ['int', [1, -7], 'not-int'];
        yield 'string' => ['string', ['a', 'b'], 1];
        yield 'bool' => ['bool', [true, false], 1];
        yield 'float' => ['float', [1.5, 2.5], 1];
        yield 'array' => ['array', [[1], [2]], 1];
        yield 'iterable' => ['iterable', [[1, 2], new \ArrayObject([3])], 1];
        yield 'callable' => ['callable', [static fn(): int => 1, 'strlen'], '__no_fn__'];
    }

    /** @param list<mixed> $valid */
    #[DataProvider('pseudoTypeAddProvider')]
    public function testPseudoTypeEmptyThenAddAcceptsAndRejects(string $type, array $valid, mixed $wrong): void {
        $v = self::build(Vector::class, $type);
        foreach ($valid as $i => $item) {
            $v->add($i, $item);
            self::assertSame($item, $v->get($i));
        }
        self::assertCount(count($valid), $v);

        $this->expectException(InvalidArgumentException::class);
        $v->add(count($valid), $wrong);
    }

    public function testObjectInference(): void {
        $vector = Vector::of(HelloWorld::class);
        $vector[] = new HelloWorld();
        $item = $vector->get(0);
        self::assertInstanceOf(HelloWorld::class, $item);
        self::assertSame('Hello, World!', $item->sayHello());
    }

}

class HelloWorld {
    
    public function sayHello(): string {
        return "Hello, World!";
    }

    public function sayGoodbye(): string {
        return "Goodbye, World!";
    }
}
