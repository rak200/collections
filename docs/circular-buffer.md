# CircularBuffer

[← Reference](README.md)

Fixed-capacity FIFO with overwrite-on-full semantics: once the buffer is full, pushing a new item evicts the oldest and hands it back.

```php
use Rak200\Collections\CircularBuffer;
```

Reach for it to keep only the last N items: sliding windows, in-memory log ringbuffers, recent-activity feeds, rate-limit windows.

## Contents

- [Construction — any / of](#construction)
- [push — and the evicted item](#push--and-the-evicted-item)
- [pop / peek](#pop--peek)
- [capacity / isFull](#capacity--isfull)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

`CircularBuffer` is the one collection whose factories take an extra leading argument: the capacity comes first, before the type. It has no pseudo-type factories (`ofInt()` and friends) — the capacity-first signature does not fit the shared trait — so scalar buffers go through `any()`.

```php
$recent = CircularBuffer::any(3);                       // capacity 3, untyped
$logs   = CircularBuffer::of(100, LogLine::class);      // capacity 100, typed
$window = CircularBuffer::any(10, [1, 2, 3]);           // capacity 10, seeded
```

Capacity must be a positive integer:

```php
CircularBuffer::any(0);    // InvalidArgumentException
CircularBuffer::any(-1);   // InvalidArgumentException
```

Initial items are pushed in order, and eviction applies **during** the seeding — passing more items than the capacity is not an error, it just means only the last `$capacity` survive:

```php
CircularBuffer::any(2, [1, 2, 3, 4])->toArray();   // [3, 4]
```

Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#circularbuffer)

---

## push — and the evicted item

`push()` appends at the newest end, O(1). Its return value is what distinguishes this class: `null` while there was room, and the **evicted oldest item** once the buffer is full.

```php
$recent = CircularBuffer::any(3);

$recent->push('a');   // null — had room
$recent->push('b');   // null
$recent->push('c');   // null — now full
$recent->push('d');   // 'a'  — evicted the oldest

$recent->toArray();   // ['b', 'c', 'd']
$recent->count();     // 3 — never exceeds the capacity
```

That return value is the hook for "handle the item that fell out of the window":

```php
$evicted = $window->push($sample);
if ($evicted !== null) {
    $runningSum -= $evicted;
}
```

Because `null` is also what a non-full push returns, a buffer that legitimately stores `null` cannot distinguish the two — check `isFull()` before pushing in that case.

[↑ Back to top](#circularbuffer)

---

## pop / peek

`pop()` removes and returns the **oldest** item — this is a FIFO, so it drains from the same end eviction takes from. `peek()` reads that item without removing. Both are O(1) and both return `null` when empty.

```php
$b = CircularBuffer::any(3, ['a', 'b', 'c']);

$b->peek();   // 'a' — oldest, still buffered
$b->pop();    // 'a' — removed
$b->pop();    // 'b'
$b->pop();    // 'c'
$b->pop();    // null — empty, does not throw
```

Popping frees room, so the next `push()` returns `null` again rather than evicting.

[↑ Back to top](#circularbuffer)

---

## capacity / isFull

`capacity()` is the fixed size the buffer was built with — it never changes. `isFull()` is `count() === capacity()`, and is what tells you whether the next `push()` will evict.

```php
$b = CircularBuffer::any(2);
$b->capacity();   // 2
$b->isFull();     // false

$b->push('a');
$b->push('b');
$b->isFull();     // true — the next push evicts

$b->pop();
$b->isFull();     // false
```

`clear()` empties the buffer but keeps the capacity and the type.

[↑ Back to top](#circularbuffer)

---

## Iteration and toArray

Both go **oldest → newest**, which is also eviction order. The iteration key is a zero-based offset from the oldest item, not a storage slot — the underlying ring wraps, but that never leaks out.

```php
$b = CircularBuffer::any(3);
$b->push('a');
$b->push('b');
$b->push('c');
$b->push('d');   // evicts 'a'

foreach ($b as $age => $value) {
    echo "$age: $value\n";   // 0: b, 1: c, 2: d
}

$b->toArray();   // ['b', 'c', 'd']
```

`getType()`, `count()`, and `isEmpty()` behave as described in [`AbstractCollection`](abstract-collection.md).

[↑ Back to top](#circularbuffer)
