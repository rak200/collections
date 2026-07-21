# Internal\LinkedNode

[← Reference](README.md)

The node handle [`LinkedList`](linked-list.md) hands back from `push()` / `unshift()` / `insertBefore()` / `insertAfter()` and takes in for O(1) splicing.

```php
use Rak200\Collections\Internal\LinkedNode;
```

`final`, and `@internal` in the sense that you never construct one — the list does. But instances cross the public API boundary, so its shape is part of what you work with.

## Contents

- [value](#value)
- [owner](#owner)
- [prev / next](#prev--next)

---

## value

The element the node carries, `public readonly`. Set at construction and never reassigned — to change what a position holds, remove the node and insert a new one.

```php
$list = LinkedList::ofString(['a', 'b']);
$node = $list->head();

$node->value;    // 'a'
$node->value = 'z';   // Error: Cannot modify readonly property
```

[↑ Back to top](#internallinkednode)

---

## owner

`public readonly` back-reference to the list that created the node. It is what lets `LinkedList::remove()` reject a foreign node instead of corrupting two lists:

```php
$a = LinkedList::ofInt([1]);
$node = $a->head();

$node->owner === $a;      // true
$b = LinkedList::ofInt([2]);
$b->remove($node);        // InvalidArgumentException: Node does not belong to this list.
```

Note that `insertBefore()` / `insertAfter()` do not consult `owner` — only `remove()` does.

[↑ Back to top](#internallinkednode)

---

## prev / next

The neighbouring nodes, or `null` at the boundaries. They are **public and writable**, because the list has to relink them from the outside, but they are maintained by `LinkedList` — writing to them yourself detaches nodes and leaves `count()` lying.

Read them to walk the list manually when you want the nodes rather than the values:

```php
$list = LinkedList::ofString(['a', 'b', 'c']);

for ($n = $list->head(); $n !== null; $n = $n->next) {
    echo $n->value;   // a, b, c
}

$list->head()->prev;   // null — the head has no predecessor
$list->tail()->next;   // null
```

After `LinkedList::remove()`, the removed node's `prev` and `next` are both reset to `null`, so a stale handle is inert rather than pointing back into the list.

[↑ Back to top](#internallinkednode)
