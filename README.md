# collections

[![CI](https://github.com/rak200/collections/actions/workflows/ci.yml/badge.svg)](https://github.com/rak200/collections/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/rak200/collections/graph/badge.svg)](https://codecov.io/gh/rak200/collections)
[![Infection MSI](https://img.shields.io/badge/mutation%20MSI-100%25-brightgreen)](infection.json5.dist)
[![Latest tag](https://img.shields.io/github/v/tag/rak200/collections?sort=semver)](https://github.com/rak200/collections/tags)
[![PHP](https://img.shields.io/badge/php-8.4%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen?logo=php&logoColor=white)](https://phpstan.org/)
[![Code style](https://img.shields.io/badge/code%20style-PHP--CS--Fixer-blue?logo=php&logoColor=white)](.php-cs-fixer.dist.php)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![SemVer](https://img.shields.io/badge/semver-2.0.0-blue)](https://semver.org/spec/v2.0.0.html)
[![Keep a Changelog](https://img.shields.io/badge/changelog-Keep%20a%20Changelog-orange)](CHANGELOG.md)

Typed generic collections for PHP 8.4+.

See the [`docs/` reference](docs/README.md) for the full per-class API with runnable examples.

## Installation

Not published on Packagist — install straight from the GitHub repository as a Composer VCS package. Its dependencies (`rak200/caster`, `rak200/utils`) are VCS-only too, and Composer reads `repositories` only from the root project, so the consuming project must list **all three**:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/rak200/collections" },
        { "type": "vcs", "url": "https://github.com/rak200/caster" },
        { "type": "vcs", "url": "https://github.com/rak200/utils" }
    ]
}
```

then require it as usual:

```bash
composer require rak200/collections
```

## Provided types

| Class                                  | Purpose                                                                |
|----------------------------------------|------------------------------------------------------------------------|
| `Rak200\Collections\Vector`            | Int-indexed dynamic array of typed (or mixed) values                   |
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

## Construction

Collections are built through **static factories**, not `new` (constructors are `protected`). The factory carries the element type so both PHPStan and IDE tooling infer it — `Vector::ofInt()` is a `Vector<int>`, `Vector::of(User::class)` a `Vector<User>`.

| Factory                              | Element type                                              |
|--------------------------------------|-----------------------------------------------------------|
| `X::any()`                           | untyped (`mixed` — no runtime enforcement)                |
| `X::of(Foo::class)`                  | instances of `Foo`                                        |
| `X::ofInt()` / `ofString()` / `ofBool()` / `ofFloat()` / `ofObject()` / `ofCallable()` | the matching PHP built-in type |

Key/value collections take the key discriminator first — `Map::of('string', Foo::class)`, `BiMap::of('int', Foo::class)`, `ObjectMap::of(Key::class, Value::class)` — or `X::any()` for untyped. `CircularBuffer` keeps capacity first: `CircularBuffer::any(3)` / `CircularBuffer::of(3, Foo::class)`. `OrderedSet` takes an optional comparator last: `OrderedSet::of(Foo::class, comparator: $cmp)`.

> `LinkedList`, `Queue`, and `Deque` still expose public (soft-`@deprecated`) constructors because they are composed by other collections; prefer their factories in new code.

## Usage

### Typed vector

Reach for it when you need an ordered, int-indexed list of items with type enforcement (DTO collections, search results, paginated rows).

```php
use Rak200\Collections\Vector;

$users = Vector::of(User::class);
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
$bag = Vector::any();              // accepts any value (scalar or object)
$bag[] = 42;
$bag[] = 'hello';
$bag[] = $anything;
```

### Pseudo-typed collection

The single-value collections expose a factory per PHP built-in type — `ofInt()`, `ofString()`, `ofBool()`, `ofFloat()`, `ofObject()`, `ofCallable()` — alongside `of(Foo::class)` and `any()`.

```php
$counts = Vector::ofInt();
$counts->add(0, 42);               // ok
$counts->add(1, 'three');          // InvalidArgumentException

$labels = Set::ofString();
$pq     = PriorityQueue::ofCallable();
```

### Doubly linked list

Reach for it when you need to splice items in or out at arbitrary positions and can keep a node handle (LRU caches, free lists, playlists with mid-sequence edits).

```php
use Rak200\Collections\LinkedList;

$list = LinkedList::of(Task::class);
$first  = $list->push($a);           // returns the node
$second = $list->push($b);
$list->insertBefore($second, $c);    // O(1)
$list->remove($first);               // O(1)
```

### Queue (FIFO)

Reach for it for background-job processing, BFS frontiers, message buffers — anywhere "first in, first out" is the rule.

```php
use Rak200\Collections\Queue;

$jobs = Queue::of(Job::class);
$jobs->enqueue($job1);
$jobs->enqueue($job2);
$jobs->peek();      // $job1
$jobs->dequeue();   // $job1
```

### Stack (LIFO)

Reach for it for undo / redo, DFS / backtracking, parser scopes, expression evaluation — anywhere the most recent push should come out first.

```php
use Rak200\Collections\Stack;

$undo = Stack::of(Action::class);
$undo->push($action1);
$undo->push($action2);
$undo->pop();       // $action2
```

### Set (unique by identity)

Reach for it for membership tests, deduplication, visited-node tracking in graph traversals, tag / permission collections.

```php
use Rak200\Collections\Set;

$visited = Set::of(Node::class);
$visited->add($node);     // true
$visited->add($node);     // false — already present
$visited->contains($node); // true
```

### Map (typed key/value)

Reach for it for keyed lookups (id → entity, slug → page, code → label), in-memory indexes / caches, and config bags with typed values.

```php
use Rak200\Collections\Map;

$index = Map::of('string', User::class);
$index->set('alice', $alice);
$index->get('alice');     // $alice
$index->has('bob');       // false
$index->remove('alice');  // true

foreach ($index as $key => $user) {
    // ...
}
```

### Priority queue

Reach for it for scheduling (Dijkstra / A* frontier, urgent-first jobs), event simulation, top-N extraction — anywhere "process the most important next" is the rule.

```php
use Rak200\Collections\PriorityQueue;

$pq = PriorityQueue::of(Job::class);
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
$visited = OrderedSet::of(Node::class);
$visited->add($n1);
$visited->add($n2);
$visited->first();     // $n1

// Sorted by custom comparator
$byScore = fn(Player $a, Player $b) => $b->score <=> $a->score;
$leaderboard = OrderedSet::of(Player::class, comparator: $byScore);
$leaderboard->add($alice);
$leaderboard->add($bob);
$leaderboard->first(); // highest-score player
```

### Bidirectional map

Reach for it for session-id ↔ user, slug ↔ entity, enum-code ↔ label tables — any one-to-one relation you want to query from either side.

```php
use Rak200\Collections\BiMap;

$sessions = BiMap::of('string', User::class);
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
$metadata = ObjectMap::of(User::class, AuditEntry::class);
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

$headers = MultiMap::any();        // string keys → string values (untyped)
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

$words = MultiSet::ofString(['the', 'quick', 'the', 'fox', 'the', 'fox']);
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

$buffer = Deque::ofString();
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

$recent = CircularBuffer::any(3);  // capacity 3, untyped
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

$primes = ImmutableSet::ofInt([2, 3, 5, 7]);
$primes->contains(5);             // true
$primes->union(Set::ofInt([11]));    // new ImmutableSet([2, 3, 5, 7, 11])
$primes->intersection(ImmutableSet::ofInt([3, 5, 13])); // new ImmutableSet([3, 5])

// Convert an existing mutable Set into a read-only snapshot.
$snapshot = ImmutableSet::fromSet($mutableSet);
```

### Immutable map

Reach for it for frozen configuration / feature flags, lookup tables built once at boot, defensive returns from getters that must forbid caller mutation.

```php
use Rak200\Collections\ImmutableMap;

$config = ImmutableMap::any([
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
composer test       # PHPUnit 13
composer phpstan    # PHPStan level max
composer cs-check   # PHP-CS-Fixer, @PhpCsFixer preset (dry-run)
composer infection  # Infection mutation testing, minMsi/minCoveredMsi 100
```

The suite uses PHPUnit 13 and covers every `src/` class. Each class has a paired `tests/*Test.php` exercising construction, type enforcement, public API, and interface compliance. `composer phpstan` runs level-max static analysis, including a project extension (`phpstan/CollectionTypeResolver.php`) that binds each collection's generics from its factory/constructor discriminator strings. `composer infection` gates test *quality* — every mutant Infection can generate must be killed or provably equivalent (see `@infection-ignore-all` comments in `src/`).

## Versioning

Follows [Semantic Versioning](https://semver.org) — still pre-1.0 while the API stabilizes. The `Latest tag` badge above tracks the pushed git tag automatically; see `CLAUDE.md` for the release checklist.

## License

MIT
