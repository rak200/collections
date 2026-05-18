<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Internal\ValidatesType;

final class ValidatesTypeTest extends TestCase {

    public function testMixedSkipsCheckForAnyValue(): void {
        ValidatesType::checkType('mixed', new \stdClass());
        ValidatesType::checkType('mixed', 42);
        ValidatesType::checkType('mixed', 'string');
        ValidatesType::checkType('mixed', null);
        ValidatesType::checkType('mixed', [1, 2, 3]);
        ValidatesType::checkType('mixed', true);
        self::assertTrue(true);
    }

    public function testObjectAcceptsAnyObject(): void {
        ValidatesType::checkType('object', new \stdClass());
        ValidatesType::checkType('object', new \DateTimeImmutable());
        ValidatesType::checkType('object', new \ArrayObject());
        self::assertTrue(true);
    }

    public function testObjectRejectsScalar(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of object. Got: int');
        ValidatesType::checkType('object', 42);
    }

    public function testObjectRejectsNull(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of object. Got: null');
        ValidatesType::checkType('object', null);
    }

    public function testObjectRejectsArray(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of object. Got: array');
        ValidatesType::checkType('object', [1, 2]);
    }

    public function testIntAcceptsInt(): void {
        ValidatesType::checkType('int', 42);
        ValidatesType::checkType('int', 0);
        ValidatesType::checkType('int', -7);
        self::assertTrue(true);
    }

    public function testIntegerAliasAcceptsInt(): void {
        ValidatesType::checkType('integer', 42);
        self::assertTrue(true);
    }

    public function testIntRejectsStringAndFloat(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of int. Got: string');
        ValidatesType::checkType('int', '42');
    }

    public function testIntRejectsFloat(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType('int', 3.14);
    }

    public function testStringAcceptsString(): void {
        ValidatesType::checkType('string', 'hello');
        ValidatesType::checkType('string', '');
        self::assertTrue(true);
    }

    public function testStringRejectsInt(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of string. Got: int');
        ValidatesType::checkType('string', 42);
    }

    public function testBoolAcceptsBool(): void {
        ValidatesType::checkType('bool', true);
        ValidatesType::checkType('bool', false);
        self::assertTrue(true);
    }

    public function testBooleanAliasAcceptsBool(): void {
        ValidatesType::checkType('boolean', true);
        self::assertTrue(true);
    }

    public function testBoolRejectsInt(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType('bool', 1);
    }

    public function testFloatAcceptsFloat(): void {
        ValidatesType::checkType('float', 3.14);
        ValidatesType::checkType('float', 0.0);
        self::assertTrue(true);
    }

    public function testDoubleAliasAcceptsFloat(): void {
        ValidatesType::checkType('double', 3.14);
        self::assertTrue(true);
    }

    public function testFloatRejectsInt(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType('float', 42);
    }

    public function testArrayAcceptsArray(): void {
        ValidatesType::checkType('array', []);
        ValidatesType::checkType('array', [1, 2, 3]);
        ValidatesType::checkType('array', ['a' => 1]);
        self::assertTrue(true);
    }

    public function testArrayRejectsIterableObject(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType('array', new \ArrayObject([1, 2]));
    }

    public function testIterableAcceptsArrayAndTraversable(): void {
        ValidatesType::checkType('iterable', [1, 2]);
        ValidatesType::checkType('iterable', new \ArrayObject([1, 2]));
        self::assertTrue(true);
    }

    public function testIterableRejectsScalar(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType('iterable', 42);
    }

    public function testCallableAcceptsClosureAndStringFunctions(): void {
        ValidatesType::checkType('callable', fn() => null);
        ValidatesType::checkType('callable', 'strlen');
        self::assertTrue(true);
    }

    public function testCallableRejectsNonCallableString(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType('callable', 'not-a-real-function-xyz');
    }

    public function testClassStringAcceptsInstance(): void {
        ValidatesType::checkType(\DateTimeImmutable::class, new \DateTimeImmutable());
        self::assertTrue(true);
    }

    public function testClassStringRejectsDifferentClass(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Item must be an instance of %s. Got: stdClass',
            \DateTimeImmutable::class
        ));
        ValidatesType::checkType(\DateTimeImmutable::class, new \stdClass());
    }

    public function testClassStringRejectsScalar(): void {
        $this->expectException(InvalidArgumentException::class);
        ValidatesType::checkType(\DateTimeImmutable::class, 'not-a-date');
    }

    public function testLabelIsInterpolatedIntoErrorMessage(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be an instance of stdClass. Got: int');
        ValidatesType::checkType(\stdClass::class, 42, 'Key');
    }

    public function testLabelAppliesToPseudoTypesToo(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an instance of int. Got: string');
        ValidatesType::checkType('int', 'not-an-int', 'Value');
    }

    public function testDefaultLabelIsItem(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of stdClass');
        ValidatesType::checkType(\stdClass::class, 42);
    }
}
