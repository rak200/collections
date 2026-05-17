# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/collections** is a standalone PHP 8.4+ library providing typed generic collection types. It depends on `rak200/caster` for the `ToArray` contract.

## Structure

```
collections/
├── composer.json
└── src/
    ├── AbstractCollection.php    # Shared base: $items, $type, Iterator/Countable/ToArray, count/toArray/getType
    ├── Vector.php                # Int-indexed dynamic array of typed/mixed values
    ├── Collection.php            # @deprecated 0.0.2; thin BC shim over Vector (string keys)
    ├── LinkedList.php            # Doubly linked list (O(1) ops via node refs)
    ├── LinkedNode.php            # Node used by LinkedList
    ├── Queue.php                 # FIFO (backed by LinkedList)
    ├── Stack.php                 # LIFO (overrides iteration for top-to-bottom)
    ├── Set.php                   # Unique elements by spl_object_id (overrides toArray)
    └── Map.php                   # Ordered key-value map with key+value typing
```

All classes live under the `Rak200\Collections` namespace (PSR-4 from `src/`).

## Classes

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
- O(1) `push()`, `unshift()`, `pop()`, `shift()`, `insertBefore()`, `insertAfter()`, `remove()` (the last four take/return `LinkedNode`)
- `head()`, `tail()` return the boundary nodes (or `null`)
- Static `fromVector(Vector $v)` builds a list from a `Vector`

**`Queue<T_Object>`**
- Implements `Iterator`, `Countable`, `ToArray`; backed internally by `LinkedList`
- Constructor: `new Queue(string $type = 'mixed', iterable $items = [])`
- Methods: `enqueue()`, `dequeue()` (returns `?T_Object`), `peek()` (returns `?T_Object`)

**`Stack<T_Object>`**
- Implements `Iterator`, `Countable`, `ToArray`
- Constructor: `new Stack(string $type = 'mixed', iterable $items = [])`
- Methods: `push()`, `pop()`, `peek()`
- Iteration yields elements from top (most recently pushed) to bottom

**`Set<T_Object>`**
- Implements `Iterator`, `Countable`, `ToArray`
- Uniqueness is by object identity (`spl_object_id`), not value equality
- Constructor: `new Set(string $type = 'mixed', iterable $items = [])`
- Methods: `add()` (returns `bool` — true if newly added), `remove()` (returns `bool`), `contains()`
- `toArray()` returns a zero-indexed array (object-id keys discarded)

**`Map<T_Key, T_Value>`**
- Implements `Iterator`, `ArrayAccess`, `Countable`, `ToArray`
- Constructor: `new Map(string $keyType = 'mixed', string $valueType = 'mixed', array $items = [])`
  - `$keyType`: `'int'`, `'string'`, or `'mixed'`
  - `$valueType`: class-string to enforce, or `'mixed'`
- Methods: `set()`, `get()`, `has()`, `delete()` (returns `bool`), `keys()`, `values()`
- Insertion order is preserved

When adding a new collection type:
1. Create the class under `src/` with namespace `Rak200\Collections`
2. Implement appropriate PHP SPL interfaces (`Iterator`, `Countable`, etc.) where it makes sense
3. Implement `Rak200\Caster\Contracts\ToArray` for consistency
4. Use generics in docblocks (`@template T of object`)

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.0.1** — unstable until unit tests are added. 

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Update `CHANGELOG.md`: add a new `## [x.y.z] - YYYY-MM-DD` section with `### Added / Changed / Fixed / Removed` entries and a comparison link at the bottom
3. Update the version reference in `README.md`
4. Commit and push
5. Create and push a git tag matching the version: `git tag x.y.z && git push origin x.y.z`

Consumers using `"type": "vcs"` in their `composer.json` resolve versions from git tags.
