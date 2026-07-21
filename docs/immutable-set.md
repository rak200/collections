# ImmutableSet

[← Reference](README.md)

Read-only counterpart to [`Set`](set.md), with the same set-algebra operations. `final`; entries are fixed at construction.

```php
use Rak200\Collections\ImmutableSet;
use Rak200\Collections\Set;
```

Reach for it for allow-lists / deny-lists, frozen membership tables, or read-only snapshots returned from an API or service layer.

## Contents

- [Construction — any / of / fromSet](#construction)
- [contains](#contains)
- [union / intersection / difference](#union--intersection--difference)
- [isSubsetOf / isSupersetOf](#issubsetof--issupersetof)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Same factory shape as `Set`, minus anything that mutates afterward. Duplicates in the initial items are silently dropped, same as `Set`.

```php
$primes = ImmutableSet::ofInt([2, 3, 5, 7]);
$typed  = ImmutableSet::of(Role::class, [$admin, $editor]);

$snapshot = ImmutableSet::fromSet($mutableSet);   // frozen copy, same type; later Set mutations don't leak through
```

Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#immutableset)

---

## contains

O(1), same [hybrid identity](internals.md#value-identity--how-duplicates-are-decided) as `Set` — objects by instance, scalars by value.

```php
$primes->contains(5);    // true
$primes->contains(4);    // false
```

[↑ Back to top](#immutableset)

---

## union / intersection / difference

Each accepts **either** an `ImmutableSet` or a mutable `Set` on the right-hand side (`self|Set`), and always returns a **new** `ImmutableSet` — the operands are never modified, which for `ImmutableSet` is simply structural (it has no mutators to begin with).

```php
$primes->union(Set::ofInt([11]));                       // ImmutableSet([2, 3, 5, 7, 11])
$primes->intersection(ImmutableSet::ofInt([3, 5, 13]));  // ImmutableSet([3, 5])
$primes->difference(ImmutableSet::ofInt([3])); // ImmutableSet([2, 5, 7])
```

The result's type always matches `$this->type`, regardless of which type `$other` was built with — mixing an untyped and a typed set is fine as long as every value satisfies the receiver's type.

[↑ Back to top](#immutableset)

---

## isSubsetOf / isSupersetOf

Same non-strict containment semantics as [`Set`](set.md#issubsetof--issupersetof), also accepting either `ImmutableSet` or `Set` on the right.

```php
$small = ImmutableSet::ofInt([2, 3]);
$small->isSubsetOf($primes);      // true
$primes->isSupersetOf($small);    // true
```

[↑ Back to top](#immutableset)

---

## Iteration and toArray

`toArray()` returns a zero-indexed list, hash keys discarded — same as `Set`:

```php
$primes->toArray();   // [2, 3, 5, 7]

foreach ($primes as $i => $p) {
    // 0 => 2, 1 => 3, ...
}
```

`count()` and `isEmpty()` behave as described in [`AbstractCollection`](abstract-collection.md), though `ImmutableSet` implements them itself over its own hash-keyed storage rather than inheriting them.

[↑ Back to top](#immutableset)
