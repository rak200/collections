# collections

Typed generic collections for PHP 8.4+.

## Installation

```bash
composer require rak200/collections
```

## Provided types

| Class                                  | Purpose                                                                |
|----------------------------------------|------------------------------------------------------------------------|
| `Rak200\Collections\Vector`            | Int-indexed dynamic array of typed (or mixed) values                   |
| `Rak200\Collections\Collection`        | **Deprecated** (0.0.2 → 1.0.0). Thin BC shim over `Vector` accepting string keys |
| `Rak200\Collections\LinkedList`  | Doubly linked list with O(1) insertion/removal at any node             |
| `Rak200\Collections\Queue`             | FIFO queue (backed by `LinkedList`)                              |
| `Rak200\Collections\Stack`             | LIFO stack                                                             |
| `Rak200\Collections\Set`               | Unique-element set (hybrid: identity for objects, value for scalars)   |
| `Rak200\Collections\Map`               | Ordered key-value map with separate key and value type enforcement     |
| `Rak200\Collections\PriorityQueue`     | Max-heap priority queue with O(log n) enqueue/dequeue, stable on ties  |
| `Rak200\Collections\OrderedSet`        | Unique-element set with insertion order or custom comparator           |
| `Rak200\Collections\BiMap`             | Bidirectional map with unique keys AND unique values (O(1) both ways)  |
| `Rak200\Collections\ObjectMap`         | Ordered map keyed by objects (identity via `spl_object_id`)            |
| `Rak200\Collections\MultiMap`          | Key-to-many-values map (HTTP headers, `groupBy` results)               |
| `Rak200\Collections\MultiSet`          | Bag / occurrence counter (frequency, histogram)                        |
| `Rak200\Collections\Deque`             | Double-ended queue (facade over `LinkedList`)                          |
| `Rak200\Collections\CircularBuffer`    | Fixed-capacity FIFO with overwrite-on-full semantics                   |
| `Rak200\Collections\ImmutableSet`      | Read-only set with set algebra                                         |
| `Rak200\Collections\ImmutableMap`      | Read-only map; mutation through `ArrayAccess` throws                   |

`Vector`, `Stack`, `Set`, `Map`, and `OrderedSet` share an `AbstractCollection` base that handles `$items` storage, `Iterator`, `Countable`, `ToArray`, and `getType()`/`count()`/`toArray()`. The concrete classes add their public API and override iteration/`toArray` only where their semantics demand it. `LinkedList`, `Queue`, `PriorityQueue`, `BiMap`, `ObjectMap`, `MultiMap`, `MultiSet`, `Deque`, `CircularBuffer`, `ImmutableSet`, and `ImmutableMap` use their own storage models and stand alone.

All types implement `Countable`, `Rak200\Caster\Contracts\ToArray`, and an appropriate iteration / array-access interface.

## Usage

### Typed vector

Reach for it when you need an ordered, int-indexed list of items with type enforcement (DTO collections, search results, paginated rows).

```php
use Rak200\Collections\Vector;

$users = new Vector(User::class);
$users->add(0, $alice);
$users->add(1, $bob);

foreach ($users as $key => $user) {
    echo "$key: {$user->name}\n";
}

echo count($users);   // 2
$users->remove(0);
```

### Untyped (mixed) vector

```php
$bag = new Vector();               // accepts any value (scalar or object)
$bag[] = 42;
$bag[] = 'hello';
$bag[] = $anything;
```

### Pseudo-typed collection

Every `$type` parameter accepts a class-string, `'mixed'` (skip), `'object'` (any object), or one of the PHP built-in pseudo-types: `'int'`/`'integer'`, `'string'`, `'bool'`/`'boolean'`, `'float'`/`'double'`, `'array'`, `'iterable'`, `'callable'`.

```php
$counts  = new Vector('int');
$counts->add(0, 42);               // ok
$counts->add(1, 'three');          // InvalidArgumentException

$labels  = new Set('string');
$weights = new Map('string', 'float');
$pq      = new PriorityQueue('callable');
```

### Doubly linked list

Reach for it when you need to splice items in or out at arbitrary positions and can keep a node handle (LRU caches, free lists, playlists with mid-sequence edits).

```php
use Rak200\Collections\LinkedList;

$list = new LinkedList(Task::class);
$first  = $list->push($a);           // returns the node
$second = $list->push($b);
$list->insertBefore($second, $c);    // O(1)
$list->remove($first);               // O(1)
```

### Queue (FIFO)

Reach for it for background-job processing, BFS frontiers, message buffers — anywhere "first in, first out" is the rule.

```php
use Rak200\Collections\Queue;

$jobs = new Queue(Job::class);
$jobs->enqueue($job1);
$jobs->enqueue($job2);
$jobs->peek();      // $job1
$jobs->dequeue();   // $job1
```

### Stack (LIFO)

Reach for it for undo / redo, DFS / backtracking, parser scopes, expression evaluation — anywhere the most recent push should come out first.

```php
use Rak200\Collections\Stack;

$undo = new Stack(Action::class);
$undo->push($action1);
$undo->push($action2);
$undo->pop();       // $action2
```

### Set (unique by identity)

Reach for it for membership tests, deduplication, visited-node tracking in graph traversals, tag / permission collections.

```php
use Rak200\Collections\Set;

$visited = new Set(Node::class);
$visited->add($node);     // true
$visited->add($node);     // false — already present
$visited->contains($node); // true
```

### Map (typed key/value)

Reach for it for keyed lookups (id → entity, slug → page, code → label), in-memory indexes / caches, and config bags with typed values.

```php
use Rak200\Collections\Map;

$index = new Map('string', User::class);
$index->set('alice', $alice);
$index->get('alice');     // $alice
$index->has('bob');       // false
$index->delete('alice');  // true

foreach ($index as $key => $user) {
    // ...
}
```

### Priority queue

Reach for it for scheduling (Dijkstra / A* frontier, urgent-first jobs), event simulation, top-N extraction — anywhere "process the most important next" is the rule.

```php
use Rak200\Collections\PriorityQueue;

$pq = new PriorityQueue(Job::class);
$pq->enqueue($urgentJob, 10);
$pq->enqueue($normalJob, 5);
$pq->enqueue($laterJob, 1);
$pq->dequeue();        // $urgentJob — highest priority first
$pq->peek();           // $normalJob
```

### Ordered set

Reach for it for leaderboards / rankings (with a comparator) or insertion-ordered distinct lists where stable `first()` / `last()` matters.

```php
use Rak200\Collections\OrderedSet;

// Insertion-ordered (default)
$visited = new OrderedSet(Node::class);
$visited->add($n1);
$visited->add($n2);
$visited->first();     // $n1

// Sorted by custom comparator
$byScore = fn(Player $a, Player $b) => $b->score <=> $a->score;
$leaderboard = new OrderedSet(Player::class, $byScore);
$leaderboard->add($alice);
$leaderboard->add($bob);
$leaderboard->first(); // highest-score player
```

### Bidirectional map

Reach for it for session-id ↔ user, slug ↔ entity, enum-code ↔ label tables — any one-to-one relation you want to query from either side.

```php
use Rak200\Collections\BiMap;

$sessions = new BiMap('string', User::class);
$sessions->put('sess-abc', $alice);
$sessions->put('sess-xyz', $bob);

$sessions->getByKey('sess-abc');  // $alice
$sessions->getByValue($alice);    // 'sess-abc' — O(1) reverse lookup
$sessions->forcePut('sess-abc', $charlie); // overwrites the existing mapping
```

### Object-keyed map

Reach for it to attach metadata / audit info / cached results to existing domain objects without modifying them.

```php
use Rak200\Collections\ObjectMap;

// Attach metadata to existing object instances without modifying them.
$metadata = new ObjectMap(User::class, AuditEntry::class);
$metadata->set($alice, $aliceAudit);
$metadata->set($bob, $bobAudit);

$metadata->get($alice);   // $aliceAudit — identity by spl_object_id
$metadata->has($bob);     // true

// Object keys aren't expressible via $map[$obj] — ObjectMap deliberately
// does not implement ArrayAccess. Use set()/get() instead.
foreach ($metadata as $user => $entry) {
    echo "{$user->name}: {$entry->summary}\n";
}
```

### MultiMap (key → many values)

Reach for it for HTTP headers (where keys can repeat), `groupBy` results, tag → entity indexes — anywhere one key naturally holds many values.

```php
use Rak200\Collections\MultiMap;

$headers = new MultiMap('string', 'string');
$headers->add('Set-Cookie', 'session=abc');
$headers->add('Set-Cookie', 'tracking=xyz');
$headers->add('Content-Type', 'text/html');

$headers->get('Set-Cookie');        // ['session=abc', 'tracking=xyz']
$headers->getFirst('Content-Type'); // 'text/html'
$headers->countKey('Set-Cookie');   // 2
$headers->count();                  // 2 — distinct keys
$headers->total();                  // 3 — total values
```

### MultiSet (bag / occurrence counter)

Reach for it for frequency tables, word counts, histograms, vote tallies — any "how many of each?" tally.

```php
use Rak200\Collections\MultiSet;

$words = new MultiSet('string', ['the', 'quick', 'the', 'fox', 'the', 'fox']);
$words->countOf('the');          // 3
$words->countOf('fox');          // 2
$words->distinct();              // 3 — unique items
$words->count();                 // 6 — total occurrences
$words->mostCommon(2);           // [['the', 3], ['fox', 2]]
```

### Deque (double-ended queue)

Reach for it for browser-style back/forward history, sliding-window scans, work-stealing queues, two-pointer algorithms.

```php
use Rak200\Collections\Deque;

$buffer = new Deque('string');
$buffer->pushBack('b');
$buffer->pushBack('c');
$buffer->pushFront('a');
$buffer->peekFront();   // 'a'
$buffer->peekBack();    // 'c'
$buffer->popFront();    // 'a'
$buffer->popBack();     // 'c'
```

### Circular buffer (fixed-capacity FIFO)

Reach for it to keep only the last N items: sliding windows, in-memory log ringbuffers, recent-activity feeds, rate-limit windows.

```php
use Rak200\Collections\CircularBuffer;

$recent = new CircularBuffer(3, 'string');
$recent->push('a');               // null  — had room
$recent->push('b');               // null
$recent->push('c');               // null
$recent->push('d');               // 'a'   — full; evicts oldest
$recent->toArray();               // ['b', 'c', 'd']
```

### Immutable set

Reach for it for allow / deny lists, frozen membership tables, or read-only snapshots returned from an API or service layer.

```php
use Rak200\Collections\ImmutableSet;
use Rak200\Collections\Set;

$primes = new ImmutableSet('int', [2, 3, 5, 7]);
$primes->contains(5);             // true
$primes->union(new Set('int', [11]));    // new ImmutableSet([2, 3, 5, 7, 11])
$primes->intersection(new ImmutableSet('int', [3, 5, 13])); // new ImmutableSet([3, 5])

// Convert an existing mutable Set into a read-only snapshot.
$snapshot = ImmutableSet::fromSet($mutableSet);
```

### Immutable map

Reach for it for frozen configuration / feature flags, lookup tables built once at boot, defensive returns from getters that must forbid caller mutation.

```php
use Rak200\Collections\ImmutableMap;

$config = new ImmutableMap('string', 'mixed', [
    'debug' => false,
    'timeout' => 30,
]);
$config->get('debug');            // false
$config['timeout'];               // 30 — read via ArrayAccess
$config['timeout'] = 60;          // BadMethodCallException
```

### Implements `Rak200\Caster\Contracts\ToArray`

```php
use Rak200\Caster\Caster;

Caster::toJson($users);            // delegates to $users->toArray()
```

## Testing

```bash
composer install
composer test
```

The suite uses PHPUnit 13 and covers every `src/` class. Each class has a paired `tests/*Test.php` exercising construction, type enforcement, public API, and interface compliance.

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.4.2** — still pre-1.0 while the API stabilizes.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Commit and push
3. `git tag 0.x.y && git push origin 0.x.y`

## License

MIT
