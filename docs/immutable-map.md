# ImmutableMap

[← Reference](README.md)

Read-only counterpart to [`Map`](map.md). `final`; entries are fixed at construction, and `ArrayAccess` writes throw.

```php
use Rak200\Collections\ImmutableMap;
```

Reach for it for frozen configuration / feature flags, lookup tables built once at boot, or defensive returns from a getter that must forbid caller mutation.

## Contents

- [Construction — any / of / fromMap](#construction)
- [get / has / keys / values](#get--has--keys--values)
- [ArrayAccess — read-only](#arrayaccess--read-only)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Same shape as `Map`'s factories, but every entry is supplied up front — there is no `set()` to call afterward.

```php
$config = ImmutableMap::any(['debug' => false, 'timeout' => 30]);   // ImmutableMap<mixed, mixed>
$typed  = ImmutableMap::of('string', User::class, ['alice' => $alice]);

$frozen = ImmutableMap::fromMap($mutableMap);   // snapshot of an existing Map, same key/value types
```

`fromMap()` copies the source `Map`'s current entries and carries its key/value types across; later mutations to the source `Map` do not affect the snapshot. Type violations raise `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#immutablemap)

---

## get / has / keys / values

Identical semantics to [`Map`](map.md#set--get--has--remove) minus anything that mutates.

```php
$config->get('debug');       // false
$config->get('missing');     // null
$config->has('timeout');     // true
$config->keys();             // ['debug', 'timeout']
$config->values();           // [false, 30]
```

[↑ Back to top](#immutablemap)

---

## ArrayAccess — read-only

Reads (`$map[$k]`, `isset($map[$k])`) work exactly like `Map`. Writes always throw `BadMethodCallException`, so the immutability guarantee extends to array syntax, not just the method API:

```php
$config['timeout'];             // 30
isset($config['timeout']);      // true

$config['timeout'] = 60;        // BadMethodCallException: ImmutableMap cannot be modified.
unset($config['timeout']);      // BadMethodCallException: ImmutableMap cannot be modified.
```

There is deliberately no `set()`, `remove()`, or `clear()` method at all — not just a throwing stub — so a static check for "does this API allow mutation" is a simple `method_exists()` away.

[↑ Back to top](#immutablemap)

---

## Iteration and toArray

Insertion order is preserved; both behave exactly like [`Map`](map.md#iteration-and-toarray)'s read side.

```php
foreach ($config as $key => $value) {
    // ...
}

$config->toArray();   // ['debug' => false, 'timeout' => 30]
```

`count()` and `isEmpty()` behave as described in [`AbstractCollection`](abstract-collection.md), though `ImmutableMap` implements them itself over its own storage rather than inheriting them.

[↑ Back to top](#immutablemap)
