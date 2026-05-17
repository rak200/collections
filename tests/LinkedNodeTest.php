<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Collections\LinkedNode;

final class LinkedNodeTest extends TestCase {

    public function testValueIsReadonly(): void {
        $node = new LinkedNode('hello');
        self::assertSame('hello', $node->value);
        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line — intentional violation to exercise readonly */
        $node->value = 'world';
    }

    public function testPrevAndNextDefaultNull(): void {
        $node = new LinkedNode(42);
        self::assertNull($node->prev);
        self::assertNull($node->next);
    }

    public function testHoldsArbitraryValueType(): void {
        $obj = new \stdClass();
        $node = new LinkedNode($obj);
        self::assertSame($obj, $node->value);
    }
}
