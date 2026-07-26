# AbstractCollection

[← Reference](README.md)

The shared base for the collections that store their elements in a plain PHP array: `$items` + `$type` storage, `Iterator` / `Countable` / `ToArray` compliance, and the default array-pointer iteration.

```php
use Rak200\Collections\AbstractCollection;
```

`Vector`, `Stack`, `Set`, `OrderedSet`, and `Map` extend it. The standalone collections — `LinkedList`, `Queue`, `Deque`, `PriorityQueue`, `BiMap`, `ObjectMap`, `MultiMap`, `MultiSet`, `CircularBuffer`, `ImmutableSet`, `ImmutableMap` — have a different storage model and implement the same interfaces themselves, so the methods below exist on them too even though they do not inherit them.

You never instantiate `AbstractCollection` directly; it is documented here so each concrete page can point at one description of the inherited behaviour instead of repeating it.

## Contents

- [getType](#gettype)
- [count / isEmpty](#count--isempty)
- [clear](#clear)
- [toArray](#toarray)
- [Iteration — current / key / next / rewind / valid](#iteration--current--key--next--rewind--valid)

---

## getType

Returns the discriminator string the collection was built with — the same string that was passed to the factory. Useful when deriving a second collection that must carry the same constraint.

```php
Vector::ofInt()->getType();            // 'int'
Vector::of(User::class)->getType();    // 'App\User'
Vector::any()->getType();              // 'mixed'
```

The key/value collections (`Map`, `BiMap`, `MultiMap`, `ObjectMap`, `ImmutableMap`) expose `getKeyType()` and `getValueType()` instead.

[↑ Back to top](#abstractcollection)

---

## count / isEmpty

`count()` is the `Countable` implementation, so `count($collection)` works directly. `isEmpty()` is the readable form of `count() === 0`.

```php
$v = Vector::ofInt([1, 2, 3]);
$v->count();     // 3
count($v);       // 3
$v->isEmpty();   // false

Vector::any()->isEmpty();  // true
```

Two collections give `count()` a domain-specific meaning, documented on their own pages: `MultiSet::count()` returns total occurrences (not distinct items), and `MultiMap::count()` returns distinct keys (not total values).

[↑ Back to top](#abstractcollection)

---

## clear

Removes every element, leaving the collection empty but keeping its type. The type constraint survives — `clear()` is a reset, not a re-type.

```php
$v = Vector::ofInt([1, 2, 3]);
$v->clear();
$v->count();    // 0
$v->getType();  // 'int' — unchanged
$v->add(0, 7);  // still enforced
```

[↑ Back to top](#abstractcollection)

---

## toArray

The `Rak200\Caster\Contracts\ToArray` implementation. Returns the elements as a plain PHP array, so any collection can be handed to `Caster`:

```php
use Rak200\Caster\Caster;

Caster::toArray(Vector::ofInt([1, 2, 3]));     // [1, 2, 3]
Caster::toJson(Vector::ofInt([1, 2, 3]), 0);   // '[1,2,3]' — Caster::toJson defaults to JSON_PRETTY_PRINT
```

The exact shape depends on the collection, because not every storage model maps onto array keys. The base implementation returns `$items` as-is; the classes that override it say so on their own page:

| Class | `toArray()` shape |
| ----- | ----------------- |
| `Vector`, `Map`, `ImmutableMap` | the underlying array, keys preserved |
| `Set`, `OrderedSet`, `ImmutableSet` | zero-indexed list — the internal hash keys are discarded |
| `Stack` | bottom to top |
| `LinkedList`, `Queue`, `Deque` | front to back |
| `CircularBuffer` | oldest to newest |
| `PriorityQueue` | highest priority first |
| `MultiMap` | `array<T_Key, list<T_Value>>` — the multi shape is preserved |
| `MultiSet` | `list<array{T_Value, int}>` of `[item, count]` pairs |
| `ObjectMap` | `list<array{T_Key, T_Value}>` of pairs — object keys are not array keys |

[↑ Back to top](#abstractcollection)

---

## Iteration — current / key / next / rewind / valid

The `Iterator` implementation, driving the internal array pointer. You call `foreach`, not these methods:

```php
foreach (Vector::ofString(['a', 'b']) as $i => $value) {
    echo "$i => $value\n";   // 0 => a, then 1 => b
}
```

`current()` and `key()` are **nullable** on the collections that iterate the backing array, so calling them past the end is well-typed rather than undefined:

```php
$v = Vector::ofInt([1]);
$v->rewind();
$v->valid();    // true
$v->current();  // 1
$v->next();
$v->valid();    // false
$v->current();  // null
$v->key();      // null
```

Iteration state is **held on the instance**, not on a detached iterator. Two nested `foreach` loops over the same collection object will interfere with each other; iterate a copy, or `toArray()`, when you need nested passes. `Stack` overrides the order (top to bottom), and `PriorityQueue`, `MultiMap`, and `MultiSet` build a snapshot on `rewind()` — see their pages.

[↑ Back to top](#abstractcollection)
