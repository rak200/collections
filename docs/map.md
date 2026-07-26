# Map

[← Reference](README.md)

Ordered key-value map with **separate** key and value type enforcement, plus `ArrayAccess`.

```php
use Rak200\Collections\Map;
```

Reach for it for keyed lookups (id → entity, slug → page, code → label), in-memory indexes and caches, and config bags with typed values.

## Contents

- [Construction — any / of](#construction)
- [getKeyType / getValueType](#getkeytype--getvaluetype)
- [set / get / has / remove](#set--get--has--remove)
- [keys / values](#keys--values)
- [ArrayAccess — `$m[$k]`, `isset`, `unset`](#arrayaccess)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

The key discriminator comes first, the value discriminator second. Keys are narrower than values: only `'int'`, `'string'`, or `'mixed'`, because PHP array keys can only be `int|string`.

```php
$index  = Map::of('string', User::class);     // Map<string, User>
$byId   = Map::of('int', User::class);        // Map<int, User>
$config = Map::any(['debug' => false]);       // Map<mixed, mixed>
```

There is no per-scalar factory for the **value** side — use `any()` when the values are scalars, or a class-string with `of()`. Both sides validate on the way in and raise `InvalidArgumentException` with a `Key` / `Value` label so you can tell which one failed. See [type discriminators](internals.md#type-discriminators).

```php
Map::of('string', User::class)->set(42, $alice);   // InvalidArgumentException: Key must be an instance of string. Got: int
```

[↑ Back to top](#map)

---

## getKeyType / getValueType

The two discriminator strings the map was built with. `Map` has two constraints, so it exposes these instead of the single `getType()` other collections carry.

```php
$m = Map::of('string', User::class);
$m->getKeyType();    // 'string'
$m->getValueType();  // 'App\User'

Map::any()->getKeyType();    // 'mixed'
```

[↑ Back to top](#map)

---

## set / get / has / remove

The core four, all O(1). `set()` overwrites an existing key rather than erroring; `get()` returns `null` for a missing key; `remove()` reports whether anything was actually removed.

```php
$index = Map::of('string', User::class);

$index->set('alice', $alice);
$index->set('alice', $alice2);   // overwrites, keeps the original position

$index->get('alice');     // $alice2
$index->get('nobody');    // null
$index->has('alice');     // true
$index->has('nobody');    // false

$index->remove('alice');  // true  — was present
$index->remove('alice');  // false — nothing to remove
```

`get()` returning `null` is ambiguous when `null` is a legitimate stored value; use `has()` to distinguish "absent" from "present and null". Overwriting an existing key does **not** move it to the end — insertion order is set by the first `set()` for that key.

Keys are matched **literally** — a key containing a dot is one key, never a path into nested values:

```php
$m = Map::any(['user.name' => 'ada', 'user' => ['name' => 'grace']]);
$m->has('user.name');   // true  — the literal key
$m->get('user.name');   // 'ada' — not 'grace'
```

[↑ Back to top](#map)

---

## keys / values

Plain PHP lists of each side, both in insertion order and index-aligned with each other.

```php
$m = Map::any(['a' => 1, 'b' => 2]);
$m->keys();     // ['a', 'b']
$m->values();   // [1, 2]
```

Both return snapshots — mutating the returned array does not touch the map.

[↑ Back to top](#map)

---

## ArrayAccess

`Map` implements the full `ArrayAccess` surface, so it reads and writes like a native associative array. Writes go through the same key and value validation as `set()`.

```php
$config = Map::any();
$config['debug'] = true;
$config['debug'];        // true
isset($config['debug']); // true
unset($config['debug']);
count($config);          // 0
```

`isset($m[$k])` follows PHP's `isset` semantics — it is `false` for a key whose stored value is `null`, even though `has()` returns `true` for it:

```php
$m = Map::any(['k' => null]);
$m->has('k');       // true
isset($m['k']);     // false
$m['k'];            // null
```

Appending with `$m[] = $v` **is** supported and mirrors PHP's own rule: the next key is the last `int` key plus one, or `0` when the map is empty or its last key is a string. The generated key is validated like any other, so it fails on a string-keyed map:

```php
$m = Map::any();
$m[] = 'first';    // key 0
$m[] = 'second';   // key 1
$m->keys();        // [0, 1]

$strKeyed = Map::of('string', User::class);
$strKeyed[] = $alice;   // InvalidArgumentException: Key must be an instance of string. Got: int
```

[↑ Back to top](#map)

---

## Iteration and toArray

Insertion order is preserved, and `foreach` yields the real keys:

```php
$index = Map::of('string', User::class);
$index->set('alice', $alice);
$index->set('bob', $bob);

foreach ($index as $key => $user) {
    echo "$key: {$user->name}\n";   // alice: …, then bob: …
}
```

`toArray()` returns the underlying `array<T_Key, T_Value>` with keys intact, so it round-trips straight back through `Map::any()` or into `Caster`:

```php
$index->toArray();   // ['alice' => $alice, 'bob' => $bob]
```

`count()`, `isEmpty()`, and `clear()` come from [`AbstractCollection`](abstract-collection.md).

For related shapes: [`BiMap`](bi-map.md) when you need reverse lookup, [`MultiMap`](multi-map.md) when one key holds many values, [`ObjectMap`](object-map.md) when the keys are objects, and [`ImmutableMap`](immutable-map.md) for a read-only snapshot.

[↑ Back to top](#map)
