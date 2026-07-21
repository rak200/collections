# MultiSet

[← Reference](README.md)

Bag / occurrence counter: like a [`Set`](set.md), but each element carries a count instead of just being present or absent.

```php
use Rak200\Collections\MultiSet;
```

Reach for it for frequency tables, word counts, histograms, vote tallies — any "how many of each?" tally.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [add / remove](#add--remove)
- [setCount](#setcount)
- [countOf / contains](#countof--contains)
- [count vs distinct](#count-vs-distinct)
- [unique / mostCommon](#unique--mostcommon)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Each initial item increments its count by one, so repeats in the input are a tally rather than a deduplication:

```php
$words = MultiSet::ofString(['the', 'quick', 'the', 'fox', 'the', 'fox']);
$words->countOf('the');   // 3
$words->distinct();       // 3
$words->count();          // 6
```

Identity is the same hybrid scheme as `Set` — objects by instance, scalars by value, no cross-type collisions. See [value identity](internals.md#value-identity--how-duplicates-are-decided). Type violations raise `InvalidArgumentException`.

[↑ Back to top](#multiset)

---

## add / remove

Both take an optional `$count` (default `1`) and both return the item's **new** count, so you rarely need a follow-up `countOf()`.

```php
$votes = MultiSet::ofString();

$votes->add('alice');       // 1
$votes->add('alice');       // 2
$votes->add('alice', 8);    // 10 — bulk increment

$votes->remove('alice', 4); // 6
$votes->remove('alice', 99);// 0 — clamps at zero and drops the item
$votes->contains('alice');  // false
```

Removing more than is present clamps to zero and deletes the entry rather than going negative or throwing. Removing something absent is a no-op returning `0`.

Both reject a non-positive `$count` — `add($x, 0)` is meaningless and `add($x, -1)` would be a disguised `remove`:

```php
$votes->add('bob', 0);    // InvalidArgumentException
$votes->remove('bob', -1);// InvalidArgumentException
```

[↑ Back to top](#multiset)

---

## setCount

Assigns an absolute count instead of adjusting a relative one — the "I already know the total" path.

```php
$tally = MultiSet::ofString();
$tally->setCount('php', 42);
$tally->countOf('php');    // 42

$tally->setCount('php', 0);
$tally->contains('php');   // false — zero deletes the entry
```

Unlike `add()` / `remove()`, `setCount()` **accepts zero** (it means "delete") but still rejects a negative count:

```php
$tally->setCount('php', -1);   // InvalidArgumentException
```

[↑ Back to top](#multiset)

---

## countOf / contains

`countOf()` is the occurrence count for one item, `0` when absent. `contains()` is the membership predicate — equivalent to `countOf($item) > 0`. Both are O(1).

```php
$words = MultiSet::ofString(['a', 'a', 'b']);

$words->countOf('a');    // 2
$words->countOf('zzz');  // 0 — absent, not an error
$words->contains('b');   // true
$words->contains('zzz'); // false
```

[↑ Back to top](#multiset)

---

## count vs distinct

The one thing to get right about this class. `count()` is the **total occurrences** across the bag; `distinct()` is the number of **unique** items.

```php
$words = MultiSet::ofString(['the', 'the', 'fox']);

$words->count();      // 3 — total occurrences
count($words);        // 3 — Countable agrees
$words->distinct();   // 2 — 'the' and 'fox'
```

So `count()` here does not match the "number of things you can iterate" meaning it has on every other collection — iteration yields two entries while `count()` says three. `isEmpty()` is true only when both are zero.

[↑ Back to top](#multiset)

---

## unique / mostCommon

`unique()` is the distinct items as a plain list, counts dropped — the `Set` view of the bag.

```php
MultiSet::ofString(['a', 'a', 'b'])->unique();   // ['a', 'b']
```

`mostCommon(int $n)` returns the top `$n` entries as `[item, count]` pairs, descending by count, with insertion order breaking ties:

```php
$words = MultiSet::ofString(['the', 'quick', 'the', 'fox', 'the', 'fox']);

$words->mostCommon(2);   // [['the', 3], ['fox', 2]]
$words->mostCommon(99);  // [['the', 3], ['fox', 2], ['quick', 1]] — capped at what exists
```

Asking for more than the bag holds returns everything rather than padding or throwing.

[↑ Back to top](#multiset)

---

## Iteration and toArray

`foreach` yields each **unique** item once, with its occurrence count exposed as the iteration **key** — an unusual shape, but the one that makes tallies read naturally:

```php
$words = MultiSet::ofString(['the', 'the', 'fox']);

foreach ($words as $count => $word) {
    echo "$word: $count\n";   // the: 2, then fox: 1
}
```

`toArray()` returns a list of `[item, count]` pairs rather than an item-keyed array, because object items cannot be array keys:

```php
$words->toArray();   // [['the', 2], ['fox', 1]]
```

`getType()` and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md).

[↑ Back to top](#multiset)
