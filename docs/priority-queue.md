# PriorityQueue

[← Reference](README.md)

Max-heap priority queue: the highest priority comes out first, ties break FIFO, and both `enqueue` and `dequeue` are O(log n).

```php
use Rak200\Collections\PriorityQueue;
```

Reach for it for scheduling (Dijkstra / A\* frontiers, urgent-first jobs), event simulation, top-N extraction — anywhere "process the most important next" is the rule.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [enqueue — priority and tie-breaking](#enqueue--priority-and-tie-breaking)
- [dequeue / peek](#dequeue--peek)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

```php
$jobs = PriorityQueue::of(Job::class);
$any  = PriorityQueue::any();
$fns  = PriorityQueue::ofCallable();
```

Items seeded through the factory are all enqueued at priority `0`, so they come out in insertion order relative to each other — and behind anything you later enqueue at a positive priority. For a meaningful ordering, call `enqueue()` with the priority you want rather than seeding.

```php
$pq = PriorityQueue::any(['a', 'b']);   // both at priority 0
$pq->enqueue('c', 1);
$pq->dequeue();   // 'c' — outranks the seeded pair
$pq->dequeue();   // 'a' — FIFO among the ties
```
 Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#priorityqueue)

---

## enqueue — priority and tie-breaking

`enqueue(mixed $item, int|float $priority)`. **Higher priority comes out first** — this is a max-heap, so a priority of `10` beats `1`. Negative priorities and floats are both fine.

```php
$pq = PriorityQueue::of(Job::class);
$pq->enqueue($laterJob,  1);
$pq->enqueue($urgentJob, 10);
$pq->enqueue($normalJob, 5);

$pq->dequeue();   // $urgentJob — highest first
```

If you think in "priority 1 is most urgent", negate on the way in: `enqueue($job, -$rank)`.

Ties are **stable, FIFO**: two items enqueued at the same priority come out in the order they went in. That is not a heap's natural behaviour — it is guaranteed here by an internal sequence counter used as the secondary key.

```php
$pq = PriorityQueue::any();
$pq->enqueue('first',  5);
$pq->enqueue('second', 5);

$pq->dequeue();   // 'first'  — enqueued earlier
$pq->dequeue();   // 'second'
```

`enqueue()` validates the item against the queue's type and is O(log n).

[↑ Back to top](#priorityqueue)

---

## dequeue / peek

`dequeue()` removes and returns the highest-priority item, O(log n). `peek()` returns it without removing, O(1). Both give `null` on an empty queue rather than throwing.

```php
$pq = PriorityQueue::any();
$pq->enqueue('low', 1);
$pq->enqueue('high', 9);

$pq->peek();      // 'high' — still queued
$pq->dequeue();   // 'high'
$pq->peek();      // 'low'
$pq->dequeue();   // 'low'
$pq->dequeue();   // null — empty, does not throw
```

Neither returns the priority — only the item. Keep the priority on the item itself if you need it downstream. As with the other collections, `null` as a return is ambiguous when `null` is a legitimate element; guard with `isEmpty()`.

[↑ Back to top](#priorityqueue)

---

## Iteration and toArray

Iteration is **non-destructive**: `rewind()` builds a sorted snapshot, so `foreach` walks highest priority to lowest without draining the queue.

```php
$pq = PriorityQueue::any();
$pq->enqueue('c', 1);
$pq->enqueue('a', 9);
$pq->enqueue('b', 5);

foreach ($pq as $i => $item) {
    echo "$i: $item\n";   // 0: a, 1: b, 2: c
}

$pq->count();     // 3 — nothing was consumed
$pq->toArray();   // ['a', 'b', 'c'] — same order, priorities dropped
```

The snapshot is built at `rewind()` and held on the instance, so mutating the queue mid-`foreach` does not affect the pass in progress, and nested loops over the same object interfere. Note that the underlying heap array is *not* in priority order — only the iteration snapshot and `toArray()` are.

Building that snapshot is O(n log n), so iterating is meaningfully more expensive than a `dequeue()` loop. When you are draining the queue anyway, drain it:

```php
while (!$pq->isEmpty()) {
    $job = $pq->dequeue();
    // ...
}
```

`getType()`, `count()`, `isEmpty()`, and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md), though `PriorityQueue` implements them itself over its heap storage.

[↑ Back to top](#priorityqueue)
