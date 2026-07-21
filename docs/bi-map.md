# BiMap

[← Reference](README.md)

Bidirectional map: keys and values are both unique, and lookup runs O(1) from either side.

```php
use Rak200\Collections\BiMap;
```

Reach for it for session-id ↔ user, slug ↔ entity, enum-code ↔ label tables — any one-to-one relation you want to query from either side.

## Contents

- [Construction — any / of](#construction)
- [getKeyType / getValueType](#getkeytype--getvaluetype)
- [put / forcePut](#put--forceput)
- [getByKey / getByValue / hasKey / hasValue](#getbykey--getbyvalue--haskey--hasvalue)
- [removeByKey / removeByValue](#removebykey--removebyvalue)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Key discriminator first, value discriminator second — same order as [`Map`](map.md). Keys are narrower than values: only `'int'`, `'string'`, or `'mixed'`.

```php
$sessions = BiMap::of('string', User::class);   // BiMap<string, User>
$any      = BiMap::any();                       // BiMap<mixed, mixed>
```

There is no per-scalar value factory; use `any()` for scalar values, or the `ConstructsProtected::build()` test helper for a non-`'mixed'` key type paired with a scalar value type. Both sides validate — see [type discriminators](internals.md#type-discriminators) — and violations raise `InvalidArgumentException` labelled `Key` or `Value`.

[↑ Back to top](#bimap)

---

## getKeyType / getValueType

The two discriminator strings the map was built with.

```php
$m = BiMap::of('string', User::class);
$m->getKeyType();    // 'string'
$m->getValueType();  // 'App\User'
```

[↑ Back to top](#bimap)

---

## put / forcePut

`put()` inserts a new pair and **throws** if either side already has a mapping; `forcePut()` inserts unconditionally, evicting whatever was on either side first. Both are O(1).

```php
$sessions = BiMap::of('string', User::class);
$sessions->put('sess-abc', $alice);
$sessions->put('sess-xyz', $bob);

$sessions->put('sess-abc', $charlie);   // InvalidArgumentException: Key 'sess-abc' is already mapped.
$sessions->put('sess-new', $alice);     // InvalidArgumentException: Value is already mapped to a different key.

$sessions->forcePut('sess-abc', $charlie);   // overwrites — 'sess-abc' now maps to $charlie, $alice is unmapped
```

`forcePut()`'s "evict both sides" behaviour matters when the incoming key *or* the incoming value was already mapped elsewhere:

```php
$m = BiMap::any();
$m->put('a', 1);
$m->put('b', 2);
$m->forcePut('a', 2);   // 'a' takes value 2 — the old ('a',1) AND the old ('b',2) are both gone
$m->hasKey('b');        // false
```

[↑ Back to top](#bimap)

---

## getByKey / getByValue / hasKey / hasValue

Lookup from either direction, all O(1). `getByValue()` hashes `$value` through the same [hybrid identity](internals.md#value-identity--how-duplicates-are-decided) scheme `Set` uses, so object values are matched by instance.

```php
$sessions->getByKey('sess-abc');   // $alice
$sessions->getByValue($alice);     // 'sess-abc'
$sessions->getByKey('missing');    // null
$sessions->getByValue($stranger);  // null

$sessions->hasKey('sess-abc');     // true
$sessions->hasValue($alice);       // true
```

[↑ Back to top](#bimap)

---

## removeByKey / removeByValue

Remove from either side; both return whether anything was actually removed, and both clean up the *other* side's index too — there is never a dangling half-pair.

```php
$sessions->removeByKey('sess-abc');    // true
$sessions->removeByKey('sess-abc');    // false — already gone
$sessions->hasValue($alice);           // false — the reverse index was cleaned up too

$sessions->removeByValue($bob);        // true — removes by scanning the value index, not the key
```

[↑ Back to top](#bimap)

---

## Iteration and toArray

Insertion order is preserved; `foreach` yields key ⇒ value pairs, and `toArray()` returns the same shape as [`Map::toArray()`](map.md#iteration-and-toarray).

```php
foreach ($sessions as $sessionId => $user) {
    // ...
}

$sessions->toArray();   // ['sess-abc' => $alice, 'sess-xyz' => $bob]
```

`count()`, `isEmpty()`, and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md), though `BiMap` implements them itself over its two parallel arrays rather than inheriting them.

[↑ Back to top](#bimap)
