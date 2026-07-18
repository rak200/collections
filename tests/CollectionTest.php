<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Collection;
use Rak200\Collections\Vector;

final class CollectionTest extends TestCase {

    /**
     * Wrap construction in an error handler that swallows the expected
     * E_USER_DEPRECATED so individual tests can focus on behavior.
     *
     * @param array<int|string, mixed> $items
     * @return Collection<mixed>
     */
    private function make(string $type = 'mixed', array $items = []): Collection {
        set_error_handler(static fn() => true, E_USER_DEPRECATED);
        try {
            return new Collection($type, $items);
        } finally {
            restore_error_handler();
        }
    }

    #[WithoutErrorHandler]
    public function testConstructorTriggersDeprecation(): void {
        $captured = null;
        set_error_handler(function (int $severity, string $msg) use (&$captured) {
            $captured = [$severity, $msg];
            return true;
        }, E_USER_DEPRECATED);
        try {
            new Collection();
        } finally {
            restore_error_handler();
        }
        self::assertNotNull($captured);
        self::assertSame(E_USER_DEPRECATED, $captured[0]);
        self::assertStringContainsString('deprecated', $captured[1]);
        self::assertStringContainsString('Vector', $captured[1]);
    }

    public function testIsAVector(): void {
        self::assertInstanceOf(Vector::class, $this->make());
    }

    public function testAddAcceptsStringKey(): void {
        $c = $this->make();
        $c->add('first', 'a');
        $c->add('second', 'b');
        self::assertSame('a', $c->get('first'));
        self::assertSame('b', $c->get('second'));
    }

    public function testAddAcceptsIntKey(): void {
        $c = $this->make();
        $c->add(7, 'seven');
        self::assertSame('seven', $c->get(7));
    }

    public function testRemoveStringKey(): void {
        $c = $this->make();
        $c->add('k', 'v');
        $c->remove('k');
        self::assertNull($c->get('k'));
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function rejectedItemProvider(): iterable {
        yield 'non-object into stdClass collection' => [\stdClass::class, 'not-an-object'];
    }

    #[DataProvider('rejectedItemProvider')]
    public function testInheritsTypeEnforcement(string $type, mixed $wrong): void {
        $c = $this->make($type);
        $this->expectException(InvalidArgumentException::class);
        $c->add('k', $wrong);
    }

    public function testArrayAccessAcceptsStringOffset(): void {
        $c = $this->make();
        $c['name'] = 'Alice';
        self::assertSame('Alice', $c['name']);
        self::assertTrue(isset($c['name']));
        unset($c['name']);
        self::assertFalse(isset($c['name']));
    }
}
