<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Set;

final class SetTest extends TestCase {

    public function testEmptySetState(): void {
        $s = Set::any();
        self::assertCount(0, $s);
        self::assertSame([], $s->toArray());
    }

    public function testAddReturnsTrueForNew(): void {
        $s = Set::any();
        $obj = new \stdClass();
        self::assertTrue($s->add($obj));
        self::assertCount(1, $s);
    }

    public function testAddReturnsFalseForDuplicateInstance(): void {
        $s = Set::any();
        $obj = new \stdClass();
        $s->add($obj);
        self::assertFalse($s->add($obj));
        self::assertCount(1, $s);
    }

    public function testDifferentInstancesAreDistinct(): void {
        $s = Set::any();
        $a = new \stdClass();
        $b = new \stdClass();
        self::assertTrue($s->add($a));
        self::assertTrue($s->add($b));
        self::assertCount(2, $s);
    }

    public function testRemoveReturnsBool(): void {
        $s = Set::any();
        $obj = new \stdClass();
        self::assertFalse($s->remove($obj));
        $s->add($obj);
        self::assertTrue($s->remove($obj));
        self::assertCount(0, $s);
    }

    public function testContains(): void {
        $s = Set::any();
        $obj = new \stdClass();
        self::assertFalse($s->contains($obj));
        $s->add($obj);
        self::assertTrue($s->contains($obj));
    }

    public function testTypeEnforcement(): void {
        $s = Set::of(\DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $s->add(new \stdClass()); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testToArrayIsZeroIndexed(): void {
        $s = Set::any();
        $a = new \stdClass();
        $b = new \stdClass();
        $s->add($a);
        $s->add($b);
        $arr = $s->toArray();
        self::assertSame([0, 1], array_keys($arr));
        self::assertSame([$a, $b], $arr);
    }

    public function testInitialItemsDeduplicated(): void {
        $a = new \stdClass();
        $s = Set::any([$a, $a, $a]);
        self::assertCount(1, $s);
    }

    public function testIteration(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $s = Set::any([$a, $b]);
        $out = [];
        foreach ($s as $item) {
            $out[] = $item;
        }
        self::assertSame([$a, $b], $out);
    }

    public function testIsAbstractCollection(): void {
        self::assertInstanceOf(AbstractCollection::class, Set::any());
    }

    public function testScalarsAreUniqueByValue(): void {
        $s = Set::any();
        self::assertTrue($s->add('foo'));
        self::assertFalse($s->add('foo'));   // same string value
        self::assertTrue($s->add(42));
        self::assertFalse($s->add(42));      // same int value
        self::assertTrue($s->add('42'));     // distinct from int 42 thanks to type prefix
        self::assertCount(3, $s);
    }

    public function testNullAndBoolsAreUnique(): void {
        $s = Set::any();
        self::assertTrue($s->add(null));
        self::assertFalse($s->add(null));
        self::assertTrue($s->add(true));
        self::assertTrue($s->add(false));
        self::assertCount(3, $s);
    }

    public function testMixedCollectionWithObjectsAndScalars(): void {
        $s = Set::any();
        $obj = new \stdClass();
        $s->add($obj);
        $s->add('foo');
        $s->add(1);
        self::assertTrue($s->contains($obj));
        self::assertTrue($s->contains('foo'));
        self::assertTrue($s->contains(1));
        self::assertCount(3, $s);
    }

    public function testIsEmptyAndClear(): void {
        $s = Set::any([1, 2, 3]);
        self::assertFalse($s->isEmpty());
        $s->clear();
        self::assertTrue($s->isEmpty());
        self::assertCount(0, $s);
    }

    public function testUnion(): void {
        $a = Set::any([1, 2, 3]);
        $b = Set::any([3, 4, 5]);
        $u = $a->union($b);
        self::assertEqualsCanonicalizing([1, 2, 3, 4, 5], $u->toArray());
        // originals untouched
        self::assertCount(3, $a);
        self::assertCount(3, $b);
    }

    public function testIntersection(): void {
        $a = Set::any([1, 2, 3, 4]);
        $b = Set::any([3, 4, 5, 6]);
        self::assertEqualsCanonicalizing([3, 4], $a->intersection($b)->toArray());
    }

    public function testDifference(): void {
        $a = Set::any([1, 2, 3, 4]);
        $b = Set::any([3, 4, 5, 6]);
        self::assertEqualsCanonicalizing([1, 2], $a->difference($b)->toArray());
    }

    /**
     * A6: HashesValues hashes arrays via md5(serialize($value)). When the
     * array contains a Closure, serialize() throws a generic Exception, not
     * the InvalidArgumentException promised by the docblock. If this test
     * passes, A6 is fixed (wrapping serialize errors as InvalidArgumentException).
     */
    public function testAddArrayContainingClosureThrowsInvalidArgument(): void {
        $s = Set::any();
        $this->expectException(InvalidArgumentException::class);
        $s->add([fn() => 1]);
    }

    public function testIsSubsetOfAndIsSupersetOf(): void {
        $small = Set::any([1, 2]);
        $large = Set::any([1, 2, 3]);
        self::assertTrue($small->isSubsetOf($large));
        self::assertFalse($large->isSubsetOf($small));
        self::assertTrue($large->isSupersetOf($small));
        self::assertFalse($small->isSupersetOf($large));
        // empty set is a subset of every set
        self::assertTrue((Set::any())->isSubsetOf($large));
    }

    public function testClassInference(): void {
        $a = Set::of(\DateTimeImmutable::class);  // hover em $a: Set<DateTimeImmutable>
        $b = Set::ofInt();                      // hover em $b: Set<int> (PHPStan; IDE mostra o template)
        $c = Set::any();                           // hover em $c: Set<mixed>

        self::assertTrue($a->add(new \DateTimeImmutable()));
        self::assertTrue($b->add(42));
        self::assertTrue($c->add('anything'));
        self::assertSame(\DateTimeImmutable::class, $a->getType());
        self::assertSame('int', $b->getType());
        self::assertSame('mixed', $c->getType());
    }
}
