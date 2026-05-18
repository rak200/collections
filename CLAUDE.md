# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/collections** is a standalone PHP 8.4+ library providing typed generic collection types. It depends on `rak200/caster` for the `ToArray` contract.

## Structure

```
collections/
├── composer.json
├── phpunit.xml
├── src/
│   ├── AbstractCollection.php    # Shared base: $items, $type, Iterator/Countable/ToArray, count/toArray/getType
│   ├── Vector.php                # Int-indexed dynamic array of typed/mixed values
│   ├── Collection.php            # @deprecated 0.0.2; thin BC shim over Vector (string keys)
│   ├── LinkedList.php            # Doubly linked list (O(1) ops via node refs)
│   ├── Queue.php                 # FIFO (backed by LinkedList)
│   ├── Stack.php                 # LIFO (overrides iteration for top-to-bottom)
│   ├── Set.php                   # Unique elements (hybrid identity: spl_object_id for objects, value for scalars)
│   ├── Map.php                   # Ordered key-value map with key+value typing
│   ├── PriorityQueue.php         # Max-heap, O(log n) enqueue/dequeue, stable on ties
│   ├── OrderedSet.php            # Set with insertion order or custom comparator
│   ├── BiMap.php                 # Bidirectional map (O(1) both ways), unique on both sides
│   ├── ObjectMap.php             # Ordered map keyed by objects (identity via spl_object_id)
│   └── Internal/
│       ├── HashesValues.php      # Trait: hybrid hash for Set/OrderedSet/BiMap/ObjectMap
│       ├── ValidatesType.php     # Trait: shared checkType() for AbstractCollection subclasses
│       └── LinkedNode.php        # Node used by LinkedList (was Rak200\Collections\LinkedNode in 0.0.x)
└── tests/                        # PHPUnit suites mirroring each src/ class
```

All classes live under the `Rak200\Collections` namespace (PSR-4 from `src/`); tests under `Rak200\Collections\Tests` (PSR-4 from `tests/`).

## Testing

`composer test` (or `vendor/bin/phpunit`) runs the suite. PHPUnit 13 is required (in `require-dev`). Each `src/X.php` has a paired `tests/XTest.php` covering construction, type enforcement, public API, interface compliance, and edge cases (empty operations, null returns, duplicates).

## Classes

All classes and members must have a docblock.

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

**`Collection<T_Value>`** — *deprecated since 0.0.2, removal in 1.0.0*
- Thin BC shim extending `Vector` so legacy callers using string keys keep working
- Overrides `add()`, `get()`, `remove()` with `int|string $offset` (vs. `int` on `Vector`)
- Triggers `E_USER_DEPRECATED` from its constructor
- New code should use `Vector` (int-indexed) or `Map` (keyed lookup)

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
- Methods: `set()`, `get()`, `has()`, `delete()` (returns `bool`), `keys()`, `values()`
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

### Internal

**`Internal\HashesValues`** (trait)
- `private static function hashValue(mixed $value): string` — used by `Set`, `OrderedSet`, `BiMap`, and `ObjectMap` to derive a uniqueness handle: `spl_object_id` for objects, value with a type prefix for scalars/null/arrays. Different types never collide (`'1'` vs `1`).

**`Internal\ValidatesType`** (trait)
- Shared `checkType()` used by `AbstractCollection` subclasses to validate items against the configured `$type` (`'mixed'` skips; class-string requires `instanceof`).

**`Internal\LinkedNode`** (final class)
- Node used by `LinkedList`. Public readonly `value`, internal-maintained `prev`/`next`. Moved from `Rak200\Collections\LinkedNode` to this namespace in 0.1.0.

When adding a new collection type:
1. Create the class under `src/` with namespace `Rak200\Collections`
2. Implement appropriate PHP SPL interfaces (`Iterator`, `Countable`, etc.) where it makes sense
3. Implement `Rak200\Caster\Contracts\ToArray` for consistency
4. Use generics in docblocks (`@template T of object`)

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.1.0** — still pre-1.0 while the API stabilizes. 

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section with `### Added / Changed / Fixed / Removed` entries and a comparison link at the bottom
3. Update the version reference in `README.md`
4. Commit and push
5. Create and push a git tag matching the version: `git tag x.y.z && git push origin x.y.z`

Consumers using `"type": "vcs"` in their `composer.json` resolve versions from git tags.
