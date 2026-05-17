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
| `Rak200\Collections\DoublyLinkedList`  | Doubly linked list with O(1) insertion/removal at any node             |
| `Rak200\Collections\Queue`             | FIFO queue (backed by `DoublyLinkedList`)                              |
| `Rak200\Collections\Stack`             | LIFO stack                                                             |
| `Rak200\Collections\Set`               | Unique-element set (identity by `spl_object_id`)                       |
| `Rak200\Collections\Map`               | Ordered key-value map with separate key and value type enforcement     |

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
use Rak200\Collections\DoublyLinkedList;

$list = new DoublyLinkedList(Task::class);
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

### Implements `Rak200\Caster\Contracts\ToArray`

```php
use Rak200\Caster\Caster;

Caster::toJson($users);            // delegates to $users->toArray()
```

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.0.2** — unstable until unit tests are added.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Commit and push
3. `git tag 0.x.y && git push origin 0.x.y`

## License

MIT
