# Reference

Per-class API reference with runnable examples. For installation and a package overview, see the [top-level README](../README.md).

| Class | Doc | What it covers |
| ----- | --- | -------------- |
| `AbstractCollection` | [abstract-collection.md](abstract-collection.md) | The shared base: `$items`/`$type` storage, `getType`/`count`/`isEmpty`/`clear`/`toArray`, and default array-pointer iteration |
| `Vector` | [vector.md](vector.md) | Int-indexed dynamic array with `ArrayAccess` |
| `LinkedList` | [linked-list.md](linked-list.md) | Doubly linked list with O(1) splicing through node handles |
| `Queue` | [queue.md](queue.md) | FIFO queue backed by `LinkedList` |
| `Stack` | [stack.md](stack.md) | LIFO stack, iterating top to bottom |
| `Deque` | [deque.md](deque.md) | Double-ended queue facade over `LinkedList` |
| `CircularBuffer` | [circular-buffer.md](circular-buffer.md) | Fixed-capacity FIFO with overwrite-on-full |
| `PriorityQueue` | [priority-queue.md](priority-queue.md) | Max-heap, O(log n) enqueue/dequeue, FIFO on ties |
| `Set` | [set.md](set.md) | Unique elements (hybrid identity) plus set algebra |
| `OrderedSet` | [ordered-set.md](ordered-set.md) | `Set` with insertion order or a custom comparator |
| `MultiSet` | [multi-set.md](multi-set.md) | Bag / occurrence counter |
| `ImmutableSet` | [immutable-set.md](immutable-set.md) | Read-only `Set` with set algebra |
| `Map` | [map.md](map.md) | Ordered key-value map with key **and** value typing |
| `BiMap` | [bi-map.md](bi-map.md) | Bidirectional map, unique on both sides, O(1) either way |
| `ObjectMap` | [object-map.md](object-map.md) | Ordered map keyed by object identity |
| `MultiMap` | [multi-map.md](multi-map.md) | Key → many values (HTTP-header style) |
| `ImmutableMap` | [immutable-map.md](immutable-map.md) | Read-only `Map`; `ArrayAccess` writes throw |
| `Internal\LinkedNode` | [linked-node.md](linked-node.md) | The node handle `LinkedList` hands back and takes in |
| `Internal\*` traits/helpers | [internals.md](internals.md) | `HashesValues`, `ProvidesValueFactories`, `ValidatesType` — how typing and identity actually work |

Type discriminators (`'int'`, `'mixed'`, `Foo::class`, …) and the factory naming scheme are shared by every class; they are described once in [internals.md](internals.md) and referenced from each page.

## Conventions used in these docs

- Output is shown in trailing `// …` comments next to each call.
- All snippets assume the `use Rak200\Collections\…;` import shown at the top of each page.
- Collections are always built through the static factories (`any()` / `of()` / `ofInt()` / …); the underlying constructors are `protected`.
- Related methods and their variants (`push`/`pop`/`peek`, `get`/`getFirst`) share a single section.
- Every method that can throw states the condition; the type-enforcement `InvalidArgumentException` is documented once per page rather than on each mutator.
