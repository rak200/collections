# collections

Typed generic collections for PHP 8.4+.

## Installation

```bash
composer require rak200/collections
```

## Provided types

| Class                            | Purpose                                          |
|----------------------------------|--------------------------------------------------|
| `Rak200\Collections\Collection`  | Typed generic array container; validates items at runtime |

Planned: `DoublyLinkedList`, `Queue`, `Stack`, `Set`, `Map`.

## Usage

### Typed collection

```php
use Rak200\Collections\Collection;

$users = new Collection(User::class);
$users->add('first', $alice);
$users->add('second', $bob);

foreach ($users as $key => $user) {
    echo "$key: {$user->name}\n";
}

echo count($users);   // 2
$users->remove('first');
```

### Untyped (mixed) collection

```php
$bag = new Collection();           // accepts any object
$bag->add(0, $anything);
```

### Implements `Rak200\Caster\Contracts\ToArray`

```php
use Rak200\Caster\Caster;

Caster::toJson($users);            // delegates to $users->toArray()
```

## Versioning

Follows [Semantic Versioning](https://semver.org). Current version: **0.0.1** — unstable until unit tests are added.

When releasing a new version:
1. Update `"version"` in `composer.json`
2. Commit and push
3. `git tag 0.x.y && git push origin 0.x.y`

## License

MIT
