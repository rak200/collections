# Changelog

All notable changes to `rak200/collections` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2026-05-17

### Added
- `isEmpty()` and `clear()` across the library — `AbstractCollection` (so `Vector`/`Stack`/`Set`/`Map`/`OrderedSet` inherit them), `LinkedList`, `Queue`, `Stack`, `BiMap`, and `PriorityQueue`. `Stack`, `LinkedList`, and `PriorityQueue` override `clear()` to reset their iteration state too.
- Set algebra on `Set` and `OrderedSet`: `union()`, `intersection()`, `difference()`, `isSubsetOf()`, `isSupersetOf()`. Returns `static`; `OrderedSet` preserves `$this`'s comparator on derived sets.
- `LinkedNode::$owner` — every node carries a reference back to its owning `LinkedList`. `LinkedList::remove()` now throws `InvalidArgumentException` when handed a node from a different list.
- `PriorityQueue` constructor accepts an initial `iterable $items = []` (each enqueued at priority 0).
- README "Planned" section listing future types (`MultiMap`, `MultiSet`, `Deque`, `CircularBuffer`, `ImmutableSet`/`ImmutableMap`).

### Changed
- **BREAKING** `Map::delete()` renamed to `Map::remove()` for naming consistency with `Set`/`LinkedList`.
- **BREAKING** `OrderedSet` constructor signature reordered from `(string $type, ?Closure $comparator, iterable $items)` to `(string $type, iterable $items = [], ?Closure $comparator = null)` so the common case (initial items, no comparator) doesn't need a `null` placeholder. Existing callers must pass `$comparator` by name or update their positional arguments.
- **BREAKING** `LinkedNode::__construct()` now takes `LinkedList $owner` as its first parameter. The class lives in `Internal\` and is only constructed by `LinkedList` itself, so direct callers should be rare.
- Class-string generics widened across the library: `class-string<T_Value&object>` → `class-string<T_Value>`. Aligns the type signatures with the `object`→`mixed` relaxation that landed in 0.1.0.
- `Set::key()` and `OrderedSet::key()` now return a sequential int index instead of the internal hash key, matching the `Iterator<int, T_Value>` contract these classes advertise.
- Many docblock improvements: explicit return/throw descriptions, and nested-iteration caveats on `LinkedList`, `Stack`, and `PriorityQueue` (instance-held cursor state — iterate `toArray()` for concurrent traversal).

### Fixed
- `Vector` constructor now rejects string keys in the `$items` array with `InvalidArgumentException`. Previously, string keys could leak through silently, contradicting the documented int-indexed shape.
- `Map::offsetSet($offset = null, $value)` (i.e. `$map[] = $value`) now uses `lastKey + 1` instead of relying on PHP's `$items[]` count-based behavior, so non-contiguous int keys no longer produce surprising next-keys.
- `PriorityQueue::current()` no longer throws a `TypeError` when called before `rewind()` — returns `null` for out-of-bounds positions instead.
- `HashesValues::hashValue()` wraps `serialize()` failures (e.g. arrays containing closures or resources) in `InvalidArgumentException`, matching the docblock promise instead of leaking the raw `Exception`.

## [0.1.0] - 2026-05-17

First minor release. Consolidates a wave of API additions and the `object`→`mixed` relaxation across the library.

### Added
- `PriorityQueue<T_Value>` — max-heap priority queue with O(log n) `enqueue`/`dequeue`, O(1) `peek`/`count`. Stable on ties via an internal sequence counter. Non-destructive iteration in extraction order.
- `OrderedSet<T_Value>` — unique-element set with a predictable iteration order. Default is insertion order; an optional `Closure $comparator` re-sorts on every `add()`. Adds `first()`/`last()`. Extends `AbstractCollection`.
- `BiMap<T_Key, T_Value>` — bidirectional map with unique keys AND unique values. O(1) `getByKey`/`getByValue`. `put()` rejects conflicts; `forcePut()` overwrites either side.
- `Internal\HashesValues` trait — hybrid hashing (`spl_object_id` for objects; value with type prefix for scalars/null/arrays) used by `Set`, `OrderedSet`, and `BiMap`.
- `Internal\ValidatesType` trait — shared `checkType()` logic factored out of `AbstractCollection` subclasses.
- PHPUnit suites for the three new classes plus scalar-value tests for `Queue`, `Stack`, `Map`, `Set`, `OrderedSet`, and `BiMap`. Suite is now 136 tests / 395 assertions.

### Changed
- **BREAKING** `Queue`, `Stack`, `Map` (value), `PriorityQueue`, `Set`, `OrderedSet`, and `BiMap` (value) all accept `mixed` values now. Method signatures widened from `object` to `mixed`; class-string enforcement still rejects non-instances (`instanceof` returns false for scalars).
- **BREAKING** `LinkedNode` moved from `Rak200\Collections\LinkedNode` to `Rak200\Collections\Internal\LinkedNode`. Consumers holding node references returned by `LinkedList::push()`/`unshift()`/etc. need to update their `use` statement; behavior unchanged.
- `Set`, `OrderedSet`, and `BiMap` now identify items via the hybrid `hashValue()` scheme: objects by identity (unchanged), scalars/null/arrays by value. Previously these classes only accepted objects.

### Fixed
- `Set` and `OrderedSet` could not store `null` as a unique value because internal presence checks used `isset()`, which returns `false` for stored `null`s. Switched to `array_key_exists()`.

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

[Unreleased]: https://github.com/rak200/collections/compare/0.1.1...HEAD
[0.1.1]: https://github.com/rak200/collections/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/rak200/collections/compare/0.0.5...0.1.0
[0.0.5]: https://github.com/rak200/collections/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/rak200/collections/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/rak200/collections/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/rak200/collections/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/rak200/collections/releases/tag/0.0.1
