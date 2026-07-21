# LinkedList

[← Reference](README.md)

Doubly linked list with O(1) insertion and removal at **any** position, through node handles.

```php
use Rak200\Collections\LinkedList;
```

Reach for it when you need to splice items in or out mid-sequence and can hold onto a node handle: LRU caches, free lists, playlists with mid-sequence edits. When you only ever touch the ends, [`Queue`](queue.md) and [`Deque`](deque.md) wrap this class behind a narrower vocabulary.

## Contents

- [Construction — any / of / ofInt / … / fromVector](#construction)
- [push / unshift / pop / shift](#push--unshift--pop--shift)
- [insertBefore / insertAfter / remove](#insertbefore--insertafter--remove)
- [head / tail](#head--tail)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

`LinkedList` still exposes a **public** constructor, soft-`@deprecated`, because `Queue` and `Deque` compose a list internally and their element type cannot flow through the `any()` factory. Prefer the factories in new code.

```php
$tasks = LinkedList::of(Task::class);
$nums  = LinkedList::ofInt([1, 2, 3]);
$any   = LinkedList::any();

$fromVector = LinkedList::fromVector(Vector::ofInt([1, 2, 3]));   // keeps the vector's type
```

`fromVector()` copies the vector's elements in order and carries its type discriminator across, so the resulting list enforces the same constraint. Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#linkedlist)

---

## push / unshift / pop / shift

The four end operations, all O(1). `push` / `unshift` return the newly created [`LinkedNode`](linked-node.md) — keep it if you intend to splice around it later. `pop` / `shift` return the **value**, or `null` on an empty list.

```php
$list = LinkedList::of(Task::class);

$first = $list->push($a);      // appends, returns the node
$last  = $list->push($b);
$head  = $list->unshift($z);   // prepends, returns the node

$list->pop();     // $b — removes and returns the tail value
$list->shift();   // $z — removes and returns the head value

LinkedList::any()->pop();   // null — empty, does not throw
```

`pop()` and `shift()` return `null` for an empty list, so guard with `isEmpty()` when `null` is a legitimate element.

[↑ Back to top](#linkedlist)

---

## insertBefore / insertAfter / remove

The reason to pick a linked list. All three take a node handle and run in O(1) — no scan, no re-indexing of anything after the splice point.

```php
$list  = LinkedList::of(Task::class);
$one   = $list->push($a);
$three = $list->push($c);

$two = $list->insertBefore($three, $b);   // a, b, c — returns the new node
$list->insertAfter($three, $d);           // a, b, c, d

$list->remove($one);                      // b, c, d
$list->toArray();                         // [$b, $c, $d]
```

`insertBefore` / `insertAfter` return the new node; `remove()` returns nothing. The inserted value is validated against the list's type, so both inserts can raise `InvalidArgumentException`.

Node ownership is guarded on `remove()` only. Every node carries a readonly `owner` back-reference, and removing a foreign node raises rather than silently corrupting two lists:

```php
$a = LinkedList::ofInt([1]);
$b = LinkedList::ofInt([2]);
$b->remove($a->head());   // InvalidArgumentException: Node does not belong to this list.
```

`insertBefore` / `insertAfter` do **not** perform that check — splicing around a node from another list corrupts both. Pass only nodes this list handed you, and drop your reference to a node once you have removed it.

[↑ Back to top](#linkedlist)

---

## head / tail

The boundary nodes — not the values — or `null` when the list is empty. Use these to start a manual walk, or to get a handle you did not keep from `push()`.

```php
$list = LinkedList::ofString(['a', 'b', 'c']);

$list->head()->value;   // 'a'
$list->tail()->value;   // 'c'

LinkedList::any()->head();   // null
```

Because they hand back nodes, `head()` and `tail()` compose with the splice methods:

```php
$list->insertAfter($list->head(), 'a2');   // a, a2, b, c
```

[↑ Back to top](#linkedlist)

---

## Iteration and toArray

`foreach` walks front to back with a zero-based `int` key, and `toArray()` returns the values in the same order:

```php
$list = LinkedList::ofString(['a', 'b']);

foreach ($list as $i => $value) {
    echo "$i: $value\n";   // 0: a, then 1: b
}

$list->toArray();   // ['a', 'b']
```

The iteration cursor lives on the **instance**, so nested `foreach` loops over the same list object interfere with each other — iterate `toArray()` when you need a nested pass. `Queue` and `Deque` delegate their iteration to the underlying list and inherit the same caveat.

`getType()`, `count()`, `isEmpty()`, and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md), though `LinkedList` implements them itself rather than inheriting them.

[↑ Back to top](#linkedlist)
