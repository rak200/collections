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

`Vector`, `Stack`, `Set`, `Map`, and `OrderedSet` share an `AbstractCollection` base that handles `$items` storage, `Iterator`, `Countable`, `ToArray`, and `getType()`/`count()`/`toArray()`. The concrete classes add their public API and override iteration/`toArray` only where their semantics demand it. `LinkedList`, `Queue`, `PriorityQueue`, and `BiMap` use their own storage models and stand alone.

All types implement `Countable`, `Rak200\Caster\Contracts\ToArray`, and an appropriate iteration / array-access interface.

## Usage

### Typed vector

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

### Doubly linked list

```php
use Rak200\Collections\LinkedList;

$list = new LinkedList(Task::class);
$first  = $list->push($a);           // returns the node
$second = $list->push($b);
$list->insertBefore($second, $c);    // O(1)
$list->remove($first);               // O(1)
```

### Queue (FIFO)

```php
use Rak200\Collections\Queue;

$jobs = new Queue(Job::class);
$jobs->enqueue($job1);
$jobs->enqueue($job2);
$jobs->peek();      // $job1
$jobs->dequeue();   // $job1
```

### Stack (LIFO)

```php
use Rak200\Collections\Stack;

$undo = new Stack(Action::class);
$undo->push($action1);
$undo->push($action2);
$undo->pop();       // $action2
```

### Set (unique by identity)

```php
use Rak200\Collections\Set;

$visited = new Set(Node::class);
$visited->add($node);     // true
$visited->add($node);     // false — already present
$visited->contains($node); // true
```

### Map (typed key/value)

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

```php
use Rak200\Collections\BiMap;

$sessions = new BiMap('string', User::class);
$sessions->put('sess-abc', $alice);
$sessions->put('sess-xyz', $bob);

$sessions->getByKey('sess-abc');  // $alice
$sessions->getByValue($alice);    // 'sess-abc' — O(1) reverse lookup
$sessions->forcePut('sess-abc', $charlie); // overwrites the existing mapping
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

Follows [Semantic Versioning](https://semver.org). Current version: **0.1.0** — still pre-1.0 while the API stabilizes.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Commit and push
3. `git tag 0.x.y && git push origin 0.x.y`

## License

MIT
