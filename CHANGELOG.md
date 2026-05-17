# Changelog

All notable changes to `rak200/collections` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.5] - 2026-05-17

### Added
- PHPUnit 13 test suite under `tests/` covering every `src/` class (91 tests, 178 assertions). `phpunit.xml` config and `composer test` script added.
- `LinkedList::getType()` getter, matching the other collection types' API.

## [0.0.4] - 2026-05-17

### Added
- `AbstractCollection<T_Value>` — new abstract base class shared by `Vector`, `Stack`, `Set`, and `Map`. Provides `$items`/`$type` storage, `Iterator`/`Countable`/`ToArray` defaults, and `getType()`/`count()`/`toArray()`. `LinkedList` and `Queue` keep their own storage model and do not extend it.
- README "Planned types" section listing `PriorityQueue`, `OrderedSet`, and `BiMap` as future additions.

### Changed
- `Vector`, `Stack`, `Set`, `Map` refactored to extend `AbstractCollection`, deduplicating iteration and storage boilerplate. Public APIs are unchanged. `$items` and `$type` are now `protected` (were `private`) — a relaxation, not a break.

## [0.0.3] - 2026-05-16

### Changed
- **BREAKING** `DoublyLinkedList` renamed to `LinkedList` and `DoublyLinkedListNode` to `LinkedNode`. `LinkedList::fromVector()` keeps the same name. No deprecation shim — consumers on `0.0.2` must update class references.

## [0.0.2] - 2026-05-16

### Added
- `Vector<T_Value>` — int-indexed dynamic array of typed or mixed values (replaces the former `Collection` shape).
- `DoublyLinkedList<T_Value>` and `DoublyLinkedListNode<T_Value>` — doubly linked list with O(1) insertion/removal at any node.
- `Queue<T_Object>` — FIFO backed by `DoublyLinkedList`, with `enqueue()`, `dequeue()`, `peek()`.
- `Stack<T_Object>` — LIFO with `push()`, `pop()`, `peek()`; iteration yields top-to-bottom.
- `Set<T_Object>` — unique-element set keyed by `spl_object_id`; `add()`/`remove()` return `bool`.
- `Map<T_Key, T_Value>` — ordered key-value map with separate key (`'int'|'string'|'mixed'`) and value type enforcement.
- `DoublyLinkedList::fromVector()` — build a list from a `Vector` (replaces `fromCollection`).

### Changed
- **BREAKING** `Collection` renamed to `Vector`; key type narrowed from `int|string` to `int` and value type widened from `object` to `mixed`.
- `DoublyLinkedList` now accepts values of any type (`mixed`), not only `object`. Class enforcement still works when `$type` is a class-string.

### Deprecated
- `Collection` is now a thin BC shim extending `Vector` that still accepts `int|string` keys. Triggers `E_USER_DEPRECATED`. Will be removed in **1.0.0** — migrate to `Vector` (int-indexed) or `Map` (keyed lookup).

## [0.0.1] - 2026-05-16

### Added
- Initial release with `Collection<T_Key, T_Object>` — typed generic array container implementing `Iterator`, `ArrayAccess`, `Countable`, and `Rak200\Caster\Contracts\ToArray`.

[Unreleased]: https://github.com/rak200/collections/compare/0.0.5...HEAD
[0.0.5]: https://github.com/rak200/collections/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/rak200/collections/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/rak200/collections/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/rak200/collections/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/rak200/collections/releases/tag/0.0.1
