<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use Dom\NodeList;
use PhpParser\Node\Expr\List_;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Internal\LinkedNode;
use Rak200\Collections\LinkedList;

final class LinkedNodeTest extends TestCase {

    /**
     * Test readonly assignment and immutability of the value property.
     * @return void
     */
    public function testValueIsReadonly(): void {
        $node = new LinkedNode(new LinkedList(), 'hello');
        self::assertSame('hello', $node->value);
        $this->expectException(\Error::class);
        /** @noinspection PhpReadonlyPropertyWrittenOutsideOfClassInspection */
        // @phpstan-ignore-next-line
        $node->value = 'world';
    }

    public function testPrevAndNextDefaultNull(): void {
        $node = new LinkedNode(new LinkedList(), 42);
        self::assertNull($node->prev);
        self::assertNull($node->next);
    }

    public function testHoldsArbitraryValueType(): void {
        $obj = new \stdClass();
        $node = new LinkedNode(new LinkedList(), $obj);
        self::assertSame($obj, $node->value);
    }

    public function testNodeOwner(): void {
        $obj = new \stdClass();
        $list = new LinkedList();
        $node = new LinkedNode($list, $obj);
        self::assertSame($list, $node->owner);
    }

    public function testInvalidNodeOwner(): void {
        $obj = new \stdClass();
        $list = new LinkedList();
        $node = new LinkedNode(new LinkedList(), $obj);
        $this->expectException(\InvalidArgumentException::class);
        $list->remove($node);
    }
}
