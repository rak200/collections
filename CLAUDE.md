# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**rak200/collections** is a standalone PHP 8.4+ library providing typed generic collection types. It depends on `rak200/caster` for the `ToArray` contract.

## Structure

```
collections/
├── composer.json
└── src/
    └── Collection.php   # Generic typed container
```

All classes live under the `Rak200\Collections` namespace (PSR-4 from `src/`).

## Classes

**`Collection<T_Key, T_Object>`**
- Implements `Iterator`, `ArrayAccess`, `Countable`, `Rak200\Caster\Contracts\ToArray`
- Constructor: `new Collection(string $type = 'mixed', array $items = [])`
  - `$type`: class-string to enforce, or `'mixed'` to skip
  - Throws `InvalidArgumentException` if any item is not an instance of `$type`
- Methods: `add()`, `get()`, `remove()`, plus standard PHP iteration/array-access/counting
- `toArray()` returns the underlying array

## Planned types

- `DoublyLinkedList` — doubly linked list with O(1) insertion/removal at any node
- `Queue` — FIFO
- `Stack` — LIFO
- `Set` — unique element set
- `Map` — ordered key-value map

When adding a new collection type:
1. Create the class under `src/` with namespace `Rak200\Collections`
2. Implement appropriate PHP SPL interfaces (`Iterator`, `Countable`, etc.) where it makes sense
3. Implement `Rak200\Caster\Contracts\ToArray` for consistency
4. Use generics in docblocks (`@template T of object`)

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.0.1** — unstable until unit tests are added.

Release process:
1. Update `"version"` in `composer.json`
2. Commit and push
3. `git tag 0.x.y && git push origin 0.x.y`

Consumers using `"type": "vcs"` resolve versions from git tags.
