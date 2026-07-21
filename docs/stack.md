# Stack

[← Reference](README.md)

LIFO stack: the most recently pushed element is the first one out, and iteration walks top to bottom.

```php
use Rak200\Collections\Stack;
```

Reach for it for undo/redo, DFS and backtracking, parser scopes, expression evaluation — anywhere the most recent push should come out first.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [push / pop / peek](#push--pop--peek)
- [Iteration — top to bottom](#iteration--top-to-bottom)
- [Inherited — getType / count / isEmpty / clear / toArray](#inherited)

---

## Construction

```php
$undo   = Stack::of(Action::class);
$frames = Stack::ofString(['a', 'b', 'c']);   // 'c' ends up on top
$any    = Stack::any();
```

Initial items are pushed in iteration order, so the **last** item of the input is the top of the stack. Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#stack)

---

## push / pop / peek

`push()` adds to the top, `pop()` removes and returns it, `peek()` returns it without removing. All three are O(1).

```php
$undo = Stack::of(Action::class);
$undo->push($action1);
$undo->push($action2);

$undo->peek();   // $action2 — still on the stack
$undo->pop();    // $action2 — removed
$undo->pop();    // $action1
$undo->pop();    // null — empty, does not throw
$undo->peek();   // null
```

Both `pop()` and `peek()` return `null` on an empty stack rather than throwing. If `null` is a legitimate element of your stack, guard with `isEmpty()` instead of a null check. `push()` validates against the stack's type.

[↑ Back to top](#stack)

---

## Iteration — top to bottom

`Stack` overrides the inherited iteration so `foreach` yields elements in pop order — top first — which is what "walking the stack" almost always means:

```php
$s = Stack::ofString(['bottom', 'middle', 'top']);

foreach ($s as $depth => $value) {
    echo "$depth: $value\n";   // 0: top, 1: middle, 2: bottom
}
```

The key is a zero-based offset **from the top**, not a storage index.

Iterating does **not** consume the stack; the elements are still there afterwards. Note that `toArray()` is *not* reordered — it returns bottom to top, the underlying storage order:

```php
$s->toArray();   // ['bottom', 'middle', 'top']
$s->count();     // 3 — iteration consumed nothing
```

[↑ Back to top](#stack)

---

## Inherited

`getType()`, `count()`, `isEmpty()`, `clear()`, and `toArray()` come from [`AbstractCollection`](abstract-collection.md).

```php
$s = Stack::ofInt([1, 2, 3]);
$s->getType();   // 'int'
$s->isEmpty();   // false
$s->clear();
$s->isEmpty();   // true
```

[↑ Back to top](#stack)
