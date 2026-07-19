# Changelog

All notable changes to `rak200/collections` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.6.0] - 2026-07-19

### Added
- **Big-O complexity documentation across every collection.** Each public operation's docblock now carries a `Complexity: O(...).` note, and every class docblock gains a `Complexity:` block grouping its methods by cost (e.g. `O(1): add / remove / contains`, `O(n): toArray / union / …`) so the right structure can be picked at a glance. Notable call-outs are documented inline: `Set`/`OrderedSet` `key()` resolving the iteration position in O(n) (a keyed `foreach` is O(n²)), `OrderedSet::add()` being O(n log n) with a comparator, `PriorityQueue` `enqueue`/`dequeue` at O(log n), and `MultiMap`/`MultiSet`/`PriorityQueue` iteration building a snapshot on `rewind()`.

### Removed
- **BREAKING — `Collection` removed.** The BC shim `@deprecated` since 0.0.2 (a thin `Vector` subclass accepting string keys, triggering `E_USER_DEPRECATED` from its constructor) is gone. Migrate to `Vector` (int-indexed sequences) or `Map` (keyed lookups). Its `CollectionTypeResolver` PHPStan entry and test were dropped as well.

## [0.5.0] - 2026-07-18

Construction moves from `new` to typed static factories, and constructors are
now `protected`. The change closes a long-standing type-inference gap: a
discriminator string passed to the constructor (`new Vector('int')`) can't be
resolved to `Vector<int>` by IDE tooling, whereas a factory with a plain
`@return self<int>` is understood everywhere.

### Added
- **Typed static factories on every collection.** Construction is now done through factories instead of `new`:
  - Pseudo-type factories (from the new `Internal\ProvidesValueFactories` trait) on the single-value collections — `Vector`, `Set`, `Stack`, `OrderedSet`, `MultiSet`, `PriorityQueue`, `ImmutableSet`, `LinkedList`, `Queue`, `Deque`: `any()`, `ofObject()`, `ofInt()`, `ofString()`, `ofBool()`, `ofFloat()`, `ofCallable()` — each returns the statically-inferred element type (`Vector::ofInt()` is `Vector<int>`).
  - Class-string factory `of()` on every collection, declared inline per class (a per-call method template doesn't resolve through a trait in IDE analysis): `Vector::of(Foo::class)` → `Vector<Foo>`. Key/value collections take the key discriminator first — `Map::of('string', Foo::class)`, `BiMap::of('string', Foo::class)`, `ImmutableMap::of('string', Foo::class)`, `ObjectMap::of(Key::class, Value::class)`, `MultiMap::of('string', Foo::class)`.
  - `any()` on the key/value and object-keyed collections — `Map::any()`, `BiMap::any()`, `MultiMap::any()`, `ObjectMap::any()`, `ImmutableMap::any()`.
  - `CircularBuffer::any(int $capacity, ...)` / `CircularBuffer::of(int $capacity, string $class, ...)` keep capacity as the first argument.
  - `OrderedSet::of()` / `OrderedSet::any()` take the optional `?Closure $comparator` as their last argument.
  - `ImmutableSet::fromSet()` and `ImmutableMap::fromMap()` are unchanged.
- **PHPStan level-9 static analysis** wired into the project. A custom `ExpressionTypeResolverExtension` (`phpstan/CollectionTypeResolver.php`, namespace `Rak200\Collections\PHPStan`) binds each collection's generic parameters from the discriminator strings its constructor/factory receives, mapping pseudo-types (`'int'`, `'string'`, `'bool'`, `'float'`, `'array'`, `'iterable'`, `'callable'`, `'object'`, `'mixed'`) and class-strings to the corresponding PHPStan types. Registered in `phpstan.neon.dist`; `composer phpstan` runs the analysis. `phpstan/phpstan ^2.0` added to `require-dev`.
- `TypedFactoriesTest` covering the new factory surface. Full suite is now 351 tests / 864 assertions.

### Changed
- **BREAKING — constructors are now `protected`** on `AbstractCollection`, `Vector`, `Set`, `Stack`, `Map`, `OrderedSet`, `MultiSet`, `PriorityQueue`, `BiMap`, `ObjectMap`, `MultiMap`, `CircularBuffer`, `ImmutableSet`, and `ImmutableMap`. Callers using `new X(...)` must switch to the static factories (`X::of(...)`, `X::any()`, `X::ofInt()`, …). This is the mechanism that makes the element type inferable by IDE tooling.
- **`Iterator::current()` and `Iterator::key()` return types widened to nullable** (`?T_Value` / `?int` etc.) on the collections that iterate the backing array, so calling them past the end of iteration is well-typed instead of relying on PHP's silent `false`/`null`.
- `Internal\ValidatesType::checkType()` first parameter relaxed from a `class-string` union to plain `string` (it already dispatched pseudo-type discriminators through a `match`); the class-string arm now guards with `is_object()` before `is_a()`.

### Notes
- **`LinkedList`, `Queue`, and `Deque` keep public constructors**, marked `@deprecated` (soft) in favor of the factories. They stay public because these collections are composed by others (`Queue`/`Deque` build a `LinkedList` internally, which can't be expressed through the `any()`-returns-`mixed` factory path); the visibility change is deferred to **1.0.0**.
- `Collection` (the 0.0.2 BC shim) keeps its public, already-`@deprecated` constructor unchanged.

## [0.4.2] - 2026-05-25

### Added
- `rak200/utils` `^1.0.0` runtime dependency. Internal call sites for `array_key_exists`/`array_keys`/`array_values`/`array_map` migrated to the shared `Rak200\Utils\Arr` helpers (`Arr::has`, `Arr::keys`, `Arr::values`, `Arr::map`) across `Set`, `OrderedSet`, `ImmutableSet`, `Map`, `MultiMap`, `ImmutableMap`, `BiMap`, `ObjectMap`, `MultiSet`, and `PriorityQueue`. No public API changes.

### Changed
- `use function` declarations across `src/` consolidated into a single grouped statement per file (`use function foo, bar, baz;`) instead of one line per name. Purely cosmetic — header is more compact, no behavior change.
- Error messages that built strings via `sprintf('... %s ...', get_debug_type($x))` switched to plain concatenation (`'... ' . get_debug_type($x)`) in `Vector`, `Map`, `BiMap`, `MultiMap`, and `Internal\ValidatesType`. `ImmutableMap` kept its `sprintf` calls. Behavior identical; messages unchanged.

## [0.4.1] - 2026-05-18

### Changed
- **Constructor `$items` parameter widened from `array` to `iterable` on `Vector`, `Map`, and `ImmutableMap`.** Brings them in line with `LinkedList`, `Queue`, `Stack`, `Set`, `OrderedSet`, `MultiSet`, `Deque`, `CircularBuffer`, `ImmutableSet`, `PriorityQueue`, and `ObjectMap`, which already accepted `iterable`. Callers passing plain arrays are unaffected (arrays are iterable).
- `composer.json` `description` updated to enumerate the full set of provided classes (was frozen at the v0.0.x list of six).

### Fixed
- `OrderedSet::key()` simplified to use `array_search()` directly, matching the cleanup that landed on `Set::key()` in 0.4.0.

### Removed
- **BREAKING** `AbstractCollection::checkType()` — the deprecated pass-through introduced for backwards compatibility in 0.3.0 has been removed. External subclasses calling `$this->checkType($item)` must switch to `Rak200\Collections\Internal\ValidatesType::checkType($this->type, $item)`. No internal call sites depended on it.

## [0.4.0] - 2026-05-18

### Added
- `MultiMap<T_Key, T_Value>` — key-to-many-values map (HTTP-header style, `groupBy` results). Each key holds an ordered list of values; the same key may appear repeatedly. Methods: `add()`/`set()`/`get()`/`getFirst()`/`has()`/`hasValue()`/`remove()`/`removeValue()`/`keys()`/`values()`/`countKey()`/`total()`, plus `isEmpty()`/`clear()`. `count()` returns the number of distinct keys; `total()` returns the total value count across all keys. Iteration yields one entry per stored value, snapshotted on `rewind()`.
- `MultiSet<T_Value>` — bag / occurrence counter (frequency, histogram). Uses the same hybrid identity as `Set` (objects by `spl_object_id`, scalars/null/arrays by value). Methods: `add($item, int $count = 1)`/`remove($item, int $count = 1)`/`setCount()`/`countOf()`/`contains()`/`distinct()`/`unique()`/`mostCommon(int $n)`, plus `isEmpty()`/`clear()`. `count()` returns the total occurrences across the bag; `distinct()` returns the number of unique items. Iteration yields the item with the occurrence count exposed as `Iterator::key()`.
- `Deque<T_Value>` — explicit double-ended queue facade over `LinkedList`. Surfaces the head/tail operations under deque vocabulary: `pushFront`/`pushBack`/`popFront`/`popBack`/`peekFront`/`peekBack`. Useful when you want either-end semantics without the linked-list node machinery.
- `CircularBuffer<T_Value>` — fixed-capacity FIFO with overwrite-on-full semantics. `push()` returns the evicted item (or `null` if the buffer had room). Internally a ring buffer over a backing array. Methods: `push()`/`pop()`/`peek()`/`capacity()`/`isFull()`, plus `isEmpty()`/`clear()`. Constructor takes `$capacity` first; non-positive capacity throws.
- `ImmutableSet<T_Value>` — read-only counterpart to `Set`. Exposes only `contains()`, the set-algebra operators (`union`/`intersection`/`difference`/`isSubsetOf`/`isSupersetOf`), and iteration / `toArray` / `count`. Set-algebra methods accept either a `Set` or another `ImmutableSet` and always return a new `ImmutableSet`. Static `ImmutableSet::fromSet(Set $s)` factory preserves the source set's type.
- `ImmutableMap<T_Key, T_Value>` — read-only counterpart to `Map`. Methods: `get()`/`has()`/`keys()`/`values()`. Still implements `ArrayAccess` for read access; `offsetSet()`/`offsetUnset()` throw `BadMethodCallException` so the immutability extends to array-access syntax. Static `ImmutableMap::fromMap(Map $m)` factory preserves the source map's key and value types.
- PHPUnit suites for each new class (`MultiMap`, `MultiSet`, `Deque`, `CircularBuffer`, `ImmutableSet`, `ImmutableMap`). Full suite is now 318 tests / 780 assertions.

### Fixed
- `Set::key()` simplified to use `array_search()` directly to find the current item's position instead of materializing an intermediate `array_flip()` lookup table on every call. Behavior unchanged.

## [0.3.0] - 2026-05-18

### Added
- **Pseudo-type discriminators** for every collection's `$type` parameter. `ValidatesType::checkType()` now recognizes PHP built-in pseudo-types in addition to class-strings: `'int'`/`'integer'`, `'string'`, `'bool'`/`'boolean'`, `'float'`/`'double'`, `'array'`, `'iterable'`, `'callable'`, plus `'object'` (any object) and `'mixed'` (skip). Collections (`Vector`, `Set`, `Stack`, `OrderedSet`, `LinkedList`, `Queue`, `PriorityQueue`, plus the value side of `Map` and `BiMap`) now accept any of these strings as their type discriminator — e.g. `new Vector('int')`, `new Set('string')`, `new Map('string', 'float')`.
- PHPUnit suite for `Internal\ValidatesType` (29 tests / 35 assertions) covering every pseudo-type and class-string.
- `PseudoTypeCollectionsTest` (27 tests / 65 assertions) — cross-collection coverage of pseudo-typed `Vector`/`Set`/`Stack`/`LinkedList`/`Queue`/`OrderedSet`/`PriorityQueue`/`Map`/`BiMap`.
- Full suite is now 237 tests / 578 assertions.

### Changed
- `Internal\ValidatesType` converted from a trait to an abstract class with a `public static function checkType(string $type, mixed $value, string $label = 'Item'): void`. Callers pass their own type as the first argument, decoupling the helper from any `$type` property on the using class. The `$label` parameter lets callers distinguish `'Item'`/`'Key'`/`'Value'` in error messages. Internal `instanceof` was replaced with `is_a()` so class-strings dispatch through the same `match` arm as pseudo-types.
- All internal call sites migrated to `ValidatesType::checkType(...)`: `Vector`, `Set`, `Stack`, `OrderedSet`, `LinkedList`, `PriorityQueue`, `Map` (value), `BiMap` (value), and `ObjectMap` (both key and value).
- `ObjectMap` drops its private `checkKey`/`checkValue` helpers in favor of the shared utility (~30 lines removed). `BiMap` and `Map` drop their private `checkValue` for the same reason; their `checkKey` methods stay because they validate scalar key types (`'int'`/`'string'`), which doesn't fit the `instanceof`-based helper.
- Error message wording unified to `'%s must be an instance of %s. Got: %s'` across all cases (class-string and pseudo-type). Tests that asserted the previous `'must be an object'`/`'must be of type X'` phrasings were updated.

### Deprecated
- `AbstractCollection::checkType()` — kept as a thin `@deprecated` pass-through to `ValidatesType::checkType()` so external subclasses calling `$this->checkType($item)` continue to work. Will be removed in **1.0.0**. New code should call `ValidatesType::checkType($this->type, $item)` directly.

## [0.2.0] - 2026-05-17

### Added
- `ObjectMap<T_Key of object, T_Value of object>` — ordered map keyed by objects. Identity is by `spl_object_id`, so two equal-but-distinct instances are different keys (same semantics as `Set`). Both keys and values are restricted to objects: `'object'` accepts any instance, or pass a `class-string` to enforce a specific class. Methods: `set()`, `get()`, `has()`, `remove()`, `keys()`, `values()`, plus `isEmpty()`/`clear()`/`count()`/`toArray()`. Iteration preserves insertion order and `Iterator::key()` yields the original object. Constructor accepts an `iterable<array{0: T_Key, 1: T_Value}>` of `[key, value]` pairs as initial entries.
- PHPUnit suite for `ObjectMap` (21 tests / 44 assertions). Full suite is now 181 tests / 501 assertions.

### Notes
- `ObjectMap` is **standalone** (does not extend `AbstractCollection`) because overriding `Iterator::key()` to return `object` would violate the parent's `int|string` return-type contract. Follows the same precedent as `BiMap` and `PriorityQueue`.
- `ObjectMap` **does not implement `ArrayAccess`** — PHP array offsets are limited to `int|string`, so `$map[$obj]` cannot be expressed. Use `set()`/`get()` instead.
- `toArray()` on `ObjectMap` returns a `list<array{T_Key, T_Value}>` of pairs because object keys cannot be expressed as plain PHP array keys.

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

[Unreleased]: https://github.com/rak200/collections/compare/0.6.0...HEAD
[0.6.0]: https://github.com/rak200/collections/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/rak200/collections/compare/0.4.2...0.5.0
[0.4.2]: https://github.com/rak200/collections/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/rak200/collections/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/rak200/collections/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/rak200/collections/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/rak200/collections/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/rak200/collections/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/rak200/collections/compare/0.0.5...0.1.0
[0.0.5]: https://github.com/rak200/collections/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/rak200/collections/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/rak200/collections/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/rak200/collections/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/rak200/collections/releases/tag/0.0.1
