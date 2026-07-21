# Deque

[← Reference](README.md)

Double-ended queue: push and pop at either end, all O(1). A thin facade over a private [`LinkedList`](linked-list.md), under deque vocabulary.

```php
use Rak200\Collections\Deque;
```

Reach for it for browser-style back/forward history, sliding-window scans, work-stealing queues, two-pointer algorithms — anywhere both ends are live.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [pushFront / pushBack](#pushfront--pushback)
- [popFront / popBack](#popfront--popback)
- [peekFront / peekBack](#peekfront--peekback)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Like `LinkedList` and `Queue`, `Deque` still exposes a **public** constructor, soft-`@deprecated`, because it composes a `LinkedList` whose type cannot flow through the `any()` factory. Prefer the factories.

```php
$history = Deque::of(Page::class);
$buffer  = Deque::ofString(['a', 'b']);   // 'a' at the front
$any     = Deque::any();
```

Initial items are pushed to the **back** in order, so the input order is the front-to-back order. Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#deque)

---

## pushFront / pushBack

Add at either end. Both are O(1) and both return `void` — unlike `LinkedList::push()`, a `Deque` hands back no node handle, because splicing mid-sequence is not part of its vocabulary. Both validate against the deque's type.

```php
$buffer = Deque::ofString();
$buffer->pushBack('b');
$buffer->pushBack('c');
$buffer->pushFront('a');

$buffer->toArray();   // ['a', 'b', 'c']
```

[↑ Back to top](#deque)

---

## popFront / popBack

Remove and return from either end, O(1), or `null` when the deque is empty.

```php
$buffer = Deque::ofString(['a', 'b', 'c']);

$buffer->popFront();   // 'a'
$buffer->popBack();    // 'c'
$buffer->popFront();   // 'b'
$buffer->popFront();   // null — empty, does not throw
```

Using `pushBack` + `popFront` gives you a FIFO queue; `pushBack` + `popBack` gives you a stack. When only one of those is what you mean, [`Queue`](queue.md) and [`Stack`](stack.md) say so in the signature.

[↑ Back to top](#deque)

---

## peekFront / peekBack

Read either end without removing, O(1), `null` when empty.

```php
$buffer = Deque::ofString(['a', 'b', 'c']);

$buffer->peekFront();   // 'a'
$buffer->peekBack();    // 'c'
$buffer->count();       // 3 — nothing was removed

Deque::any()->peekFront();   // null
```

On a single-element deque both peeks return the same element. Since `null` is also what an empty deque returns, guard with `isEmpty()` when `null` is a legitimate element.

[↑ Back to top](#deque)

---

## Iteration and toArray

Both go front to back:

```php
$d = Deque::ofString(['a', 'b']);

foreach ($d as $i => $value) {
    echo "$i: $value\n";   // 0: a, then 1: b
}

$d->toArray();   // ['a', 'b']
```

Iteration delegates to the underlying `LinkedList` cursor, which lives on the instance — nested `foreach` loops over the same deque interfere. Iterate `toArray()` when you need a nested pass.

`getType()`, `count()`, `isEmpty()`, and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md).

[↑ Back to top](#deque)
