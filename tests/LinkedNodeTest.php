<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Internal\LinkedNode;
use Rak200\Collections\LinkedList;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class LinkedNodeTest extends TestCase
{
    #[DataProvider('guardedPropertyProvider')]
    public function testValueIsReadonly(string $property): void
    {
        $node = new LinkedNode(LinkedList::any(), 'hello');
        self::assertSame('hello', $node->value);
        $this->expectException(Error::class);
        $node->{$property} = 'world';
    }

    /**
     * Test readonly assignment and immutability of the value property.
     */
    /** @return iterable<string, array{string}> */
    public static function guardedPropertyProvider(): iterable
    {
        yield 'value' => ['value'];
    }

    public function testPrevAndNextDefaultNull(): void
    {
        $node = new LinkedNode(LinkedList::any(), 42);
        self::assertNull($node->prev);
        self::assertNull($node->next);
    }

    public function testHoldsArbitraryValueType(): void
    {
        $obj = new stdClass();
        $node = new LinkedNode(LinkedList::any(), $obj);
        self::assertSame($obj, $node->value);
    }

    public function testNodeOwner(): void
    {
        $obj = new stdClass();
        $list = LinkedList::any();
        $node = new LinkedNode($list, $obj);
        self::assertSame($list, $node->owner);
    }

    public function testInvalidNodeOwner(): void
    {
        $obj = new stdClass();
        $list = LinkedList::any();
        $node = new LinkedNode(LinkedList::any(), $obj);
        $this->expectException(InvalidArgumentException::class);
        $list->remove($node);
    }
}
