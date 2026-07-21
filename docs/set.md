# Set

[← Reference](README.md)

Unique-element set with hybrid identity — objects by instance, scalars by value — plus the standard set-algebra operations.

```php
use Rak200\Collections\Set;
```

Reach for it for membership tests, deduplication, visited-node tracking in graph traversals, tag or permission collections.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [add / remove / contains](#add--remove--contains)
- [union / intersection / difference](#union--intersection--difference)
- [isSubsetOf / isSupersetOf](#issubsetof--issupersetof)
- [toArray and iteration](#toarray-and-iteration)

---

## Construction

```php
$visited = Set::of(Node::class);
$tags    = Set::ofString(['php', 'php', 'sql']);   // duplicates dropped silently
$any     = Set::any();
```

Duplicates in the initial items are dropped on the way in, so `$tags` above holds two elements. Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#set)

---

## add / remove / contains

All three are O(1) and all three report membership through a `bool` rather than throwing.

```php
$visited = Set::of(Node::class);

$visited->add($node);       // true  — newly added
$visited->add($node);       // false — already present
$visited->contains($node);  // true
$visited->remove($node);    // true  — was present, now removed
$visited->remove($node);    // false — nothing to remove
```

`add()` returning `false` is the idiomatic "have I seen this already?" test — one call instead of `contains()` plus `add()`:

```php
foreach ($nodes as $n) {
    if (!$visited->add($n)) {
        continue;   // already walked this one
    }
    // ... first visit
}
```

Identity is **hybrid**: objects match by `spl_object_id` (the same instance only), scalars, `null`, and arrays match by value, and different types never collide. Two equal-but-distinct objects are two members — see [value identity](internals.md#value-identity--how-duplicates-are-decided).

[↑ Back to top](#set)

---

## union / intersection / difference

Each returns a **new** set; neither operand is modified. The result carries `$this`'s type, so mixing a typed set with an untyped one can raise `InvalidArgumentException` from `union()` if `$other` holds values the receiver's type rejects.

```php
$a = Set::ofInt([1, 2, 3, 4]);
$b = Set::ofInt([3, 4, 5]);

$a->union($b)->toArray();          // [1, 2, 3, 4, 5]
$a->intersection($b)->toArray();   // [3, 4]
$a->difference($b)->toArray();     // [1, 2] — in $a, not in $b
$b->difference($a)->toArray();     // [5]    — difference is not symmetric
```

`union()` is O(n + m); `intersection()` and `difference()` are O(n) in the size of the receiver, since each membership test is O(1).

[↑ Back to top](#set)

---

## isSubsetOf / isSupersetOf

Containment predicates, both O(n) in the set being scanned. `isSupersetOf()` is the mirror of `isSubsetOf()`.

```php
$small = Set::ofInt([1, 2]);
$large = Set::ofInt([1, 2, 3]);

$small->isSubsetOf($large);    // true
$large->isSubsetOf($small);    // false
$large->isSupersetOf($small);  // true

Set::ofInt()->isSubsetOf($large);   // true — the empty set is a subset of everything
```

These are non-strict: a set is both a subset and a superset of itself.

[↑ Back to top](#set)

---

## toArray and iteration

`Set` overrides `toArray()` to discard the internal hash keys, so you get a clean zero-indexed list rather than the `o:12` / `i:1` handles used internally:

```php
$s = Set::ofString(['a', 'b']);
$s->toArray();       // ['a', 'b']
array_keys($s->toArray());   // [0, 1]
```

Iteration order is insertion order in practice, but `Set` does not *guarantee* it — `remove()` followed by `add()` moves an element to the end. When the order is part of your contract, use [`OrderedSet`](ordered-set.md), which promises it (and can sort by a comparator instead).

`getType()`, `count()`, `isEmpty()`, and `clear()` are inherited unchanged from [`AbstractCollection`](abstract-collection.md).

[↑ Back to top](#set)
