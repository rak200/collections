# Queue

[← Reference](README.md)

FIFO queue — first in, first out — backed internally by a [`LinkedList`](linked-list.md), so both ends are O(1).

```php
use Rak200\Collections\Queue;
```

Reach for it for background-job processing, BFS frontiers, message buffers — anywhere "first in, first out" is the rule.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [enqueue / dequeue / peek](#enqueue--dequeue--peek)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Like `LinkedList` and `Deque`, `Queue` still exposes a **public** constructor, soft-`@deprecated`, because it composes a `LinkedList` whose type cannot flow through the `any()` factory. Prefer the factories.

```php
$jobs = Queue::of(Job::class);
$ids  = Queue::ofInt([1, 2, 3]);   // 1 is at the front
$any  = Queue::any();
```

Initial items are enqueued in iteration order, so the **first** item of the input comes out first. Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#queue)

---

## enqueue / dequeue / peek

`enqueue()` adds at the back, `dequeue()` removes and returns from the front, `peek()` reads the front without removing. All O(1).

```php
$jobs = Queue::of(Job::class);
$jobs->enqueue($job1);
$jobs->enqueue($job2);

$jobs->peek();      // $job1 — still queued
$jobs->dequeue();   // $job1 — removed
$jobs->dequeue();   // $job2
$jobs->dequeue();   // null — empty, does not throw
$jobs->peek();      // null
```

Both `dequeue()` and `peek()` return `null` on an empty queue rather than throwing, which makes the drain loop read cleanly — as long as `null` is not itself a valid element:

```php
while (!$jobs->isEmpty()) {
    $job = $jobs->dequeue();
    // ...
}
```

`enqueue()` validates the item against the queue's type.

[↑ Back to top](#queue)

---

## Iteration and toArray

Both go front to back — the order things will come out — and neither consumes the queue:

```php
$q = Queue::ofString(['a', 'b', 'c']);

foreach ($q as $i => $value) {
    echo "$i: $value\n";   // 0: a, 1: b, 2: c
}

$q->toArray();   // ['a', 'b', 'c']
$q->count();     // 3 — iteration consumed nothing
```

Iteration is delegated to the underlying `LinkedList` cursor, which lives on the instance — nested `foreach` loops over the same queue interfere. Iterate `toArray()` when you need a nested pass.

`getType()`, `count()`, `isEmpty()`, and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md).

Need both ends? Use [`Deque`](deque.md). Need "most important first" rather than "oldest first"? Use [`PriorityQueue`](priority-queue.md).

[↑ Back to top](#queue)
