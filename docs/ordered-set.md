# OrderedSet

[← Reference](README.md)

A [`Set`](set.md) with a promised iteration order: insertion order by default, or a custom comparator that keeps the set sorted on every insert.

```php
use Rak200\Collections\OrderedSet;
```

Reach for it for leaderboards and rankings (with a comparator), or for insertion-ordered distinct lists where a stable `first()` / `last()` matters.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [The comparator](#the-comparator)
- [add / remove / contains](#add--remove--contains)
- [first / last](#first--last)
- [union / intersection / difference / isSubsetOf / isSupersetOf](#set-algebra)
- [toArray and iteration](#toarray-and-iteration)

---

## Construction

The comparator is the argument that distinguishes `OrderedSet` from `Set`. On `of()` it comes last, after the items; on `any()` it is the second argument. Passing it by name reads better in both:

```php
$visited = OrderedSet::of(Node::class);                     // insertion order
$sorted  = OrderedSet::ofInt([3, 1, 2], static fn ($a, $b) => $a <=> $b);
$byScore = OrderedSet::of(Player::class, comparator: $cmp);
$any     = OrderedSet::any();
```

Initial items are deduplicated and, when a comparator is present, sorted:

```php
OrderedSet::ofInt([3, 1, 2, 1], static fn ($a, $b) => $a <=> $b)->toArray();  // [1, 2, 3]
```

[↑ Back to top](#orderedset)

---

## The comparator

A `Closure` in `usort` shape — `fn(T $a, T $b): int`, negative / zero / positive. `null` (the default) means insertion order.

```php
$byScore = static fn (Player $a, Player $b): int => $b->score <=> $a->score;  // descending
$leaderboard = OrderedSet::of(Player::class, comparator: $byScore);
```

The comparator only decides **order**, never membership — uniqueness is still the hybrid identity described in [value identity](internals.md#value-identity--how-duplicates-are-decided). Two players with the same score are two distinct members that happen to tie.

The cost is worth knowing: with a comparator, the set is re-sorted on every `add()`, so insertion is O(n log n) rather than O(1). Building a large sorted set item by item is O(n² log n) — for a one-shot build, pass all items to the factory instead of adding them in a loop.

[↑ Back to top](#orderedset)

---

## add / remove / contains

Same shape as [`Set`](set.md#add--remove--contains): `add()` returns `true` when the element is new, `remove()` returns `true` when something was removed, `contains()` is a plain membership test.

```php
$s = OrderedSet::any();
$s->add('a');        // true
$s->add('a');        // false — already present
$s->contains('a');   // true
$s->remove('a');     // true
$s->remove('a');     // false
```

Without a comparator, `add()` appends — so a `remove()` + `add()` round trip moves the element to the end. With a comparator, position is always determined by the comparator, never by insertion time.

[↑ Back to top](#orderedset)

---

## first / last

The boundary elements in the set's current order, or `null` when empty. This is the payoff of the ordering guarantee — on a plain `Set` there is no defined "first".

```php
$leaderboard = OrderedSet::of(Player::class, comparator: $byScoreDesc);
$leaderboard->add($alice);
$leaderboard->add($bob);

$leaderboard->first();   // highest-score player
$leaderboard->last();    // lowest-score player

OrderedSet::any()->first();   // null
OrderedSet::any()->last();    // null
```

[↑ Back to top](#orderedset)

---

## Set algebra

`union()`, `intersection()`, `difference()`, `isSubsetOf()`, and `isSupersetOf()` behave exactly as on [`Set`](set.md#union--intersection--difference) — each operation returns a new set and leaves both operands untouched.

The one addition: the result **keeps the receiver's comparator**, so an ordered set stays ordered through the algebra.

```php
$asc = static fn ($a, $b) => $a <=> $b;
$a = OrderedSet::any([3, 1], $asc);
$b = OrderedSet::any([2, 5]);          // no comparator

$a->union($b)->toArray();   // [1, 2, 3, 5] — sorted, $a's comparator wins
$b->union($a)->toArray();   // [2, 5, 1, 3] — $b has no comparator, so the result is
                            // unsorted: $b's own order first, then $a's items in
                            // $a's order (already sorted [1, 3], not insertion order [3, 1])
```

[↑ Back to top](#orderedset)

---

## toArray and iteration

`toArray()` returns a zero-indexed list in the set's order, hash keys discarded; `foreach` walks the same order with an `int` position as the key.

```php
$s = OrderedSet::ofString(['b', 'a']);
$s->toArray();   // ['b', 'a'] — insertion order

foreach ($s as $i => $value) {
    echo "$i: $value\n";   // 0: b, then 1: a
}
```

`getType()`, `count()`, `isEmpty()`, and `clear()` are inherited from [`AbstractCollection`](abstract-collection.md); `clear()` keeps the comparator along with the type.

[↑ Back to top](#orderedset)
