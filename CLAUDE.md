# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/collections** is a standalone PHP 8.4+ library providing typed generic collection types. It depends on `rak200/caster` for the `ToArray` contract.

## Structure

```
collections/
├── composer.json
├── phpunit.xml
├── phpstan.neon.dist            # PHPStan level 9 config; registers the type-resolver extension
├── phpstan/
│   └── CollectionTypeResolver.php  # ExpressionTypeResolverExtension: binds generics from discriminator strings
├── src/
│   ├── AbstractCollection.php    # Shared base: $items, $type, Iterator/Countable/ToArray, count/toArray/getType
│   ├── Vector.php                # Int-indexed dynamic array of typed/mixed values
│   ├── LinkedList.php            # Doubly linked list (O(1) ops via node refs)
│   ├── Queue.php                 # FIFO (backed by LinkedList)
│   ├── Stack.php                 # LIFO (overrides iteration for top-to-bottom)
│   ├── Set.php                   # Unique elements (hybrid identity: spl_object_id for objects, value for scalars)
│   ├── Map.php                   # Ordered key-value map with key+value typing
│   ├── PriorityQueue.php         # Max-heap, O(log n) enqueue/dequeue, stable on ties
│   ├── OrderedSet.php            # Set with insertion order or custom comparator
│   ├── BiMap.php                 # Bidirectional map (O(1) both ways), unique on both sides
│   ├── ObjectMap.php             # Ordered map keyed by objects (identity via spl_object_id)
│   ├── MultiMap.php              # Key → many-values map (HTTP-header style)
│   ├── MultiSet.php              # Bag / occurrence counter (hybrid identity)
│   ├── Deque.php                 # Double-ended queue facade over LinkedList
│   ├── CircularBuffer.php        # Fixed-capacity FIFO with overwrite-on-full
│   ├── ImmutableSet.php          # Read-only Set with set algebra
│   ├── ImmutableMap.php          # Read-only Map (offsetSet/Unset throw)
│   └── Internal/
│       ├── HashesValues.php      # Trait: hybrid hash for Set/OrderedSet/BiMap/ObjectMap
│       ├── ProvidesValueFactories.php  # Trait: any()/ofInt()/ofString()/… factories for single-value collections
│       ├── ValidatesType.php     # Static utility (abstract class): checkType($type, $value, $label)
│       └── LinkedNode.php        # Node used by LinkedList (was Rak200\Collections\LinkedNode in 0.0.x)
└── tests/                        # PHPUnit suites mirroring each src/ class (+ ConstructsProtected trait, TypedFactoriesTest)
```

All classes live under the `Rak200\Collections` namespace (PSR-4 from `src/`); tests under `Rak200\Collections\Tests` (PSR-4 from `tests/`).

## Testing

`composer test` (or `vendor/bin/phpunit`) runs the suite. PHPUnit 13 is required (in `require-dev`). Each `src/X.php` has a paired `tests/XTest.php` covering construction, type enforcement, public API, interface compliance, and edge cases (empty operations, null returns, duplicates).

`composer phpstan` runs PHPStan level 9 (`phpstan/phpstan ^2.0`, in `require-dev`) over `phpstan/`, `src/`, and `tests/`. The project ships an `ExpressionTypeResolverExtension` — `Rak200\Collections\PHPStan\CollectionTypeResolver` in `phpstan/CollectionTypeResolver.php` — that binds each collection's generic parameters from the discriminator strings passed to its factory/constructor (`'int'` → `int`, `Foo::class` → `Foo`, etc.), so `Vector::ofInt()` resolves to `Vector<int>` even though the tooling can't otherwise infer a type from a runtime string. It's registered in `phpstan.neon.dist`; PHPStan runs the extension but the IDE (DEVSENSE) does not, which is why the factories carry plain `@return self<int>` docblocks that both understand.

Test construction: constructors are `protected` (see below), so tests build through the public factories. The few cases with no factory — the `'array'`/`'iterable'` discriminators, pseudo-typed map values, partially-typed object maps — go through the `tests/ConstructsProtected.php` trait's `build(Cls::class, ...$args)` helper (Reflection: `newInstanceWithoutConstructor()` + `getConstructor()?->invokeArgs()`, so the protected constructor's validation still fires). Negative type-rejection tests deliberately violate the static types and carry a `// @phpstan-ignore` on the offending line.

## Classes

All classes and members must have a docblock.

**Type discriminators.** Every collection's `$type` parameter (and `$valueType` on `Map`/`BiMap`) accepts:
- a class-string (validated with `is_a()`)
- `'mixed'` — skip the check
- `'object'` — any object
- a PHP built-in pseudo-type: `'int'`/`'integer'`, `'string'`, `'bool'`/`'boolean'`, `'float'`/`'double'`, `'array'`, `'iterable'`, `'callable'`

All dispatch lives in `Internal\ValidatesType::checkType()`. `Map`/`BiMap` `$keyType` is its own narrower constraint (`'int'`/`'string'`/`'mixed'`) because PHP array keys can only be `int|string`. `ObjectMap` keys and values are objects only (`'object'` or class-string).

**Construction — factories, not `new` (0.5.0).** Every collection's constructor is `protected`; collections are built through static factories so the element type is statically inferable (a discriminator string passed to `new` can't be resolved to a generic by the tooling, but a factory with a plain `@return self<int>` is). The `Constructor:` signatures documented per class below describe the underlying protected constructor — construct via these factories:
- `X::any(...)` — untyped (`mixed`); on the key/value collections (`Map`, `BiMap`, `MultiMap`, `ObjectMap`, `ImmutableMap`) it means `mixed`/`mixed`.
- `X::of(...)` — class-typed; declared **inline per class** (a per-call method template doesn't resolve through a trait in IDE analysis). Single-value: `of(Foo::class, $items)`. Key/value: `of($keyType, Foo::class, ...)`. `ObjectMap::of(Key::class, Value::class, ...)`. `CircularBuffer::of($capacity, Foo::class, ...)`. `OrderedSet::of(Foo::class, $items, ?$comparator)`.
- `X::ofInt()` / `ofString()` / `ofBool()` / `ofFloat()` / `ofObject()` / `ofCallable()` — pseudo-type factories from `Internal\ProvidesValueFactories`, on the ten single-value collections (`Vector`, `Set`, `Stack`, `OrderedSet`, `MultiSet`, `PriorityQueue`, `ImmutableSet`, `LinkedList`, `Queue`, `Deque`). Scalar **value** types on the key/value collections have no factory — use `any()` or the `ConstructsProtected::build()` test helper.
- `LinkedList`, `Queue`, `Deque` keep **public** (soft-`@deprecated`) constructors because they compose a `LinkedList` internally (whose type can't flow through `any()`); prefer their factories anyway.

**Nullable iterators (0.5.0).** On the collections that iterate the backing array, `Iterator::current()` and `Iterator::key()` return types are nullable (`?T_Value` / `?int`), so calling them past the end is well-typed.

**`AbstractCollection<T_Value>`** (abstract)
- Implements `Iterator`, `Countable`, `Rak200\Caster\Contracts\ToArray`
- Holds `protected array $items` and `protected string $type`; exposes `getType()`, `count()`, `toArray()`, and the default array-pointer iteration
- Subclasses (`Vector`, `Stack`, `Set`, `Map`) extend it; `LinkedList` and `Queue` don't (different storage model)
- Subclasses define their own type-check methods and public mutation API; `Vector`/`Map` additionally implement `ArrayAccess`; `Stack` overrides iteration (LIFO order); `Set` overrides `toArray()` to discard `spl_object_id` keys; `Map` adds its own `$keyType` field and exposes `getKeyType()`/`getValueType()`

**`Vector<T_Value>`**
- Implements `Iterator`, `ArrayAccess`, `Countable`, `Rak200\Caster\Contracts\ToArray`
- Int-indexed dynamic array of values (any type — scalar or object)
- Constructor: `new Vector(string $type = 'mixed', array $items = [])`
  - `$type`: class-string to enforce on items, or `'mixed'` to skip
  - Throws `InvalidArgumentException` if any item is not an instance of `$type`
- Methods: `add()`, `get()`, `remove()`, plus standard PHP iteration/array-access/counting
- `toArray()` returns the underlying array

**`LinkedList<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray`
- Accepts values of any type; with a class-string `$type`, items are validated as instances of that class
- Constructor: `new LinkedList(string $type = 'mixed', iterable $items = [])`
- O(1) `push()`, `unshift()`, `pop()`, `shift()`, `insertBefore()`, `insertAfter()`, `remove()` (the last four take/return `Internal\LinkedNode`)
- `head()`, `tail()` return the boundary nodes (or `null`)
- Static `fromVector(Vector $v)` builds a list from a `Vector`

**`Queue<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray`; backed internally by `LinkedList`
- Accepts values of any type
- Constructor: `new Queue(string $type = 'mixed', iterable $items = [])`
- Methods: `enqueue()`, `dequeue()` (returns `T_Value|null`), `peek()` (returns `T_Value|null`)

**`Stack<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray`
- Accepts values of any type
- Constructor: `new Stack(string $type = 'mixed', iterable $items = [])`
- Methods: `push()`, `pop()`, `peek()`
- Iteration yields elements from top (most recently pushed) to bottom

**`Set<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray`
- Hybrid identity: objects by `spl_object_id` (same instance only); scalars, null, and arrays by value
- Constructor: `new Set(string $type = 'mixed', iterable $items = [])`
- Methods: `add()` (returns `bool` — true if newly added), `remove()` (returns `bool`), `contains()`
- `toArray()` returns a zero-indexed array (internal hash keys discarded)

**`Map<T_Key, T_Value>`**
- Implements `Iterator`, `ArrayAccess`, `Countable`, `ToArray`
- Constructor: `new Map(string $keyType = 'mixed', string $valueType = 'mixed', array $items = [])`
  - `$keyType`: `'int'`, `'string'`, or `'mixed'`
  - `$valueType`: class-string to enforce on values, or `'mixed'` (any type — scalar or object)
- Methods: `set()`, `get()`, `has()`, `remove()` (returns `bool`), `keys()`, `values()`
- Insertion order is preserved

**`PriorityQueue<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — heap storage, doesn't extend `AbstractCollection`)
- Max-heap: higher priority is dequeued first; ties broken FIFO via an internal sequence counter
- Accepts values of any type
- Constructor: `new PriorityQueue(string $type = 'mixed')` (no initial items — call `enqueue` explicitly)
- Methods: `enqueue(mixed $item, int|float $priority)`, `dequeue(): T_Value|null`, `peek(): T_Value|null`
- `enqueue`/`dequeue` are O(log n); `peek`/`count` are O(1)
- Iteration is non-destructive (builds a sorted snapshot on `rewind`)

**`OrderedSet<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` via `AbstractCollection`
- Like `Set` (hybrid identity: objects by `spl_object_id`, scalars/null/arrays by value) but with predictable iteration order
- Constructor: `new OrderedSet(string $type = 'mixed', ?Closure $comparator = null, iterable $items = [])`
  - `$comparator`: `fn(T $a, T $b): int` (usort-style); `null` = insertion order
- Methods: `add()`/`remove()`/`contains()` (same shape as `Set`), plus `first()`/`last()`
- With a comparator, `uasort` runs on every `add()` (O(n log n) per insert)

**`BiMap<T_Key, T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — two parallel arrays)
- Both keys and values are unique; reverse lookup is O(1). Values can be of any type (hashed via the same hybrid scheme as `Set` — see `Internal\HashesValues`)
- Constructor: `new BiMap(string $keyType = 'mixed', string $valueType = 'mixed')`
- Methods: `put()` (throws on conflict), `forcePut()` (overwrites either side), `getByKey()`, `getByValue()`, `hasKey()`, `hasValue()`, `removeByKey()`, `removeByValue()`

**`ObjectMap<T_Key of object, T_Value of object>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — two parallel arrays keyed by object hash)
- Like `Map` but **both keys and values are objects**; identity is by `spl_object_id` on the key (same instance only — equal-but-distinct instances are different keys)
- Does **not** implement `ArrayAccess` (PHP offsets are limited to `int|string`)
- Constructor: `new ObjectMap(string $keyType = 'object', string $valueType = 'object', iterable $pairs = [])`
  - `$keyType`: class-string to enforce on keys, or `'object'` to accept any object
  - `$valueType`: class-string to enforce on values, or `'object'` to accept any object
  - `$pairs`: iterable of `[key, value]` tuples (a plain associative array can't carry object keys)
- Methods: `set()`, `get()`, `has()`, `remove()` (returns `bool`), `keys()` (`list<T_Key>`), `values()` (`list<T_Value>`)
- Insertion order is preserved
- `toArray()` returns a `list<array{T_Key, T_Value}>` of pairs (object keys aren't representable as array keys)

**`MultiMap<T_Key, T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — `array<T_Key, list<T_Value>>` storage)
- Key-to-many-values map. Each key holds an ordered list of values; the same key can be added repeatedly. Useful for HTTP headers and `groupBy` results.
- Constructor: `new MultiMap(string $keyType = 'mixed', string $valueType = 'mixed')` — no initial entries; call `add()`/`set()` explicitly
  - `$keyType`: `'int'`, `'string'`, or `'mixed'`
  - `$valueType`: full discriminator set (class-string / pseudo-type / `'mixed'`)
- Methods: `add($k, $v)`, `set($k, array $values)`, `get($k): list<T_Value>`, `getFirst($k): T_Value|null`, `has($k)`, `hasValue($k, $v)`, `remove($k): bool`, `removeValue($k, $v): bool`, `keys()`, `values()` (flattened), `countKey($k)`, `total()` (sum of all list lengths)
- `count()` returns the number of distinct keys; `total()` returns the total value count
- Iteration snapshots the flattened `[key, value]` pairs on `rewind()` (instance-held — nested iteration over the same map interferes)
- `toArray()` returns `array<T_Key, list<T_Value>>` (preserves the multi shape)

**`MultiSet<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — parallel hash arrays for items and counts)
- Bag / occurrence counter. Same hybrid identity as `Set` (`Internal\HashesValues` — objects by `spl_object_id`, scalars/null/arrays by value)
- Constructor: `new MultiSet(string $type = 'mixed', iterable $items = [])` — each initial item increments its count by one
- Methods: `add($item, int $count = 1): int`, `remove($item, int $count = 1): int`, `setCount($item, int $count)`, `countOf($item): int`, `contains($item)`, `distinct(): int`, `unique(): list<T_Value>`, `mostCommon(int $n): list<array{0: T_Value, 1: int}>` (descending by count, insertion order on ties)
- `count()` returns the total occurrences across the bag; `distinct()` returns the number of unique items
- Iteration yields each unique item with its occurrence count exposed as `Iterator::key()`
- `add`/`remove` reject `$count < 1`; `setCount` rejects negative counts (zero deletes the item)
- `toArray()` returns `list<array{T_Value, int}>` of `[item, count]` pairs (object items aren't representable as array keys)

**`Deque<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — thin facade over a private `LinkedList`)
- Double-ended queue under deque vocabulary; backed entirely by `LinkedList`'s O(1) end operations
- Constructor: `new Deque(string $type = 'mixed', iterable $items = [])` — initial items are pushed to the back in order
- Methods: `pushFront()`, `pushBack()`, `popFront(): T_Value|null`, `popBack(): T_Value|null`, `peekFront(): T_Value|null`, `peekBack(): T_Value|null`
- Iteration delegates to the underlying `LinkedList`'s cursor (front to back); shares the same single-cursor caveat
- `toArray()` returns items front-to-back

**`CircularBuffer<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — ring-buffer storage over a plain array)
- Fixed-capacity FIFO with overwrite-on-full semantics
- Constructor: `new CircularBuffer(int $capacity, string $type = 'mixed', iterable $items = [])`
  - `$capacity`: must be a positive integer; non-positive throws `InvalidArgumentException`
  - Initial items are pushed in order; if more than `$capacity` items are provided, the oldest are evicted on the fly
- Methods: `push($item): T_Value|null` (returns the evicted oldest item when full, else `null`), `pop(): T_Value|null`, `peek(): T_Value|null`, `capacity()`, `isFull()`
- Iteration yields items oldest → newest with `Iterator::key()` as a zero-based offset from the oldest
- `toArray()` returns items oldest to newest

**`ImmutableSet<T_Value>`**
- Implements `Iterator`, `Countable`, `ToArray` (standalone — same hash-keyed storage as `Set`)
- Read-only counterpart to `Set`. `final`; entries are fixed at construction.
- Hybrid identity (same as `Set`/`OrderedSet`): objects by `spl_object_id`, scalars/null/arrays by value
- Constructor: `new ImmutableSet(string $type = 'mixed', iterable $items = [])` — duplicates in `$items` are silently dropped
- Static `ImmutableSet::fromSet(Set $set): self` preserves the source set's type
- Methods: `contains()`, `union()`, `intersection()`, `difference()`, `isSubsetOf()`, `isSupersetOf()`, `isEmpty()`
- Set-algebra methods accept either a `Set` or another `ImmutableSet` (via `self|Set` union) and always return a new `ImmutableSet`; result's type matches `$this->type`
- `toArray()` returns a zero-indexed array (internal hash keys discarded)

**`ImmutableMap<T_Key, T_Value>`**
- Implements `Iterator`, `ArrayAccess`, `Countable`, `ToArray` (standalone — plain `array<T_Key, T_Value>` storage)
- Read-only counterpart to `Map`. `final`; entries are fixed at construction.
- `offsetSet()` and `offsetUnset()` always throw `BadMethodCallException` so immutability extends to `$map[$k] = $v` / `unset($map[$k])`
- Constructor: `new ImmutableMap(string $keyType = 'mixed', string $valueType = 'mixed', array $items = [])`
  - `$keyType`: `'int'`, `'string'`, or `'mixed'` (same constraint as `Map`)
  - `$valueType`: full discriminator set (class-string / pseudo-type / `'mixed'`)
- Static `ImmutableMap::fromMap(Map $map): self` preserves the source map's key and value types
- Methods: `get($k): T_Value|null`, `has($k)`, `keys()`, `values()`, `isEmpty()`, plus the read side of `ArrayAccess` (`offsetExists`, `offsetGet`)
- Insertion order is preserved
- `toArray()` returns the underlying `array<T_Key, T_Value>`

### Internal

**`Internal\HashesValues`** (trait)
- `private static function hashValue(mixed $value): string` — used by `Set`, `OrderedSet`, `BiMap`, and `ObjectMap` to derive a uniqueness handle: `spl_object_id` for objects, value with a type prefix for scalars/null/arrays. Different types never collide (`'1'` vs `1`).

**`Internal\ProvidesValueFactories`** (trait)
- Supplies the pseudo-type factories (`any()`, `ofObject()`, `ofInt()`, `ofString()`, `ofBool()`, `ofFloat()`, `ofCallable()`) to the ten single-value collections whose constructor is `__construct(string $type, iterable $items = [])`. Each returns the statically-inferred element type via a literal `@return self<int>` etc. The class-string `of()` is **not** here — a per-call method template doesn't resolve through a trait in IDE analysis, so each class declares `of()` inline.

**`Internal\ValidatesType`** (abstract class — static utility)
- `public static function checkType(string $type, mixed $value, string $label = 'Item'): void` — validates `$value` against `$type` via a `match` on the type string. Recognizes `'mixed'` (skip), `'object'` (`is_object`), PHP built-in pseudo-types `'int'`/`'integer'`, `'string'`, `'bool'`/`'boolean'`, `'float'`/`'double'`, `'array'`, `'iterable'`, `'callable'`, and falls back to `is_a($value, $type)` for class-strings. `$label` is interpolated into the error message so callers can distinguish `'Item'`/`'Key'`/`'Value'`.
- Was a trait through 0.2.0; converted to an abstract class with a static method in 0.3.0 so it can be used anywhere without depending on a `$type` property in the using class. The `AbstractCollection::checkType()` pass-through that existed for backwards compatibility was removed in 0.4.1.

**`Internal\LinkedNode`** (final class)
- Node used by `LinkedList`. Public readonly `value`, internal-maintained `prev`/`next`. Moved from `Rak200\Collections\LinkedNode` to this namespace in 0.1.0.

When adding a new collection type:
1. Create the class under `src/` with namespace `Rak200\Collections`
2. Implement appropriate PHP SPL interfaces (`Iterator`, `Countable`, etc.) where it makes sense
3. Implement `Rak200\Caster\Contracts\ToArray` for consistency
4. Use generics in docblocks (`@template T of object`)

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.5.0** — still pre-1.0 while the API stabilizes. 

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section with `### Added / Changed / Fixed / Removed` entries and a comparison link at the bottom
3. Update the version reference in `README.md`
4. Commit and push
5. Create and push a git tag matching the version: `git tag x.y.z && git push origin x.y.z`

Consumers using `"type": "vcs"` in their `composer.json` resolve versions from git tags.

## Roadmap

Pending work, without a committed target version. Item 1 is **breaking** (it only lands
in a major release); items 2–3 are non-breaking. For the deprecations, the `@deprecated`
docblocks remain the source of truth per item.

1. **Make `LinkedList`, `Queue`, and `Deque` constructors `protected`** — soft-`@deprecated`
   since 0.5.0 (`src/LinkedList.php`, `src/Queue.php`, `src/Deque.php`). They stay public
   for now because these collections are composed by others (`Queue`/`Deque` build a
   `LinkedList` internally, whose type can't flow through the `any()`-returns-`mixed`
   factory path); the pending change is to close that gap and route all construction
   through the factories.
2. **Re-add algorithmic complexity documentation** — restore the Big-O complexity notes
   for each collection's operations (e.g. `push`/`pop`/`enqueue`/`dequeue`/lookup) in the
   method docblocks, so the cost of every operation is documented at the call site.
3. **Investigate raising PHPStan to `max`** — evaluate moving the analysis from level 9
   to `max` (`phpstan.neon.dist`), assess the new findings, and adopt it if the added
   strictness is worth the churn.
