# ObjectMap

[← Reference](README.md)

Ordered map keyed by **object identity** rather than `int|string`.

```php
use Rak200\Collections\ObjectMap;
```

Reach for it to attach metadata, audit info, or cached results to existing domain objects without modifying them — anywhere the natural key is "this specific instance", not a derived id.

## Contents

- [Construction — any / of](#construction)
- [getKeyType / getValueType](#getkeytype--getvaluetype)
- [set / get / has / remove](#set--get--has--remove)
- [keys / values](#keys--values)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Both sides are objects — the discriminators are class-strings or the literal `'object'` (accept any object), never a scalar pseudo-type. `any()` accepts any object on both sides; `of()` narrows each side to a class.

```php
$metadata = ObjectMap::of(User::class, AuditEntry::class);   // ObjectMap<User, AuditEntry>
$any      = ObjectMap::any();                                // ObjectMap<object, object>
```

The constructor (and both factories) take initial entries as an **iterable of `[key, value]` pairs** — `iterable<array{0: T_Key, 1: T_Value}>` — never a plain associative array, because PHP array keys can't be objects:

```php
$m = ObjectMap::of(User::class, AuditEntry::class, [
    [$alice, $aliceAudit],
    [$bob, $bobAudit],
]);
```

Type violations raise `InvalidArgumentException` labelled `Key` or `Value` — see [type discriminators](internals.md#type-discriminators).

[↑ Back to top](#objectmap)

---

## getKeyType / getValueType

The two discriminator strings — a class-string, or `'object'` when either side accepts anything.

```php
ObjectMap::any()->getKeyType();                    // 'object'
ObjectMap::of(User::class, AuditEntry::class)->getValueType();   // 'App\AuditEntry'
```

[↑ Back to top](#objectmap)

---

## set / get / has / remove

The core four, all O(1), all keyed by `spl_object_id()` — see [value identity](internals.md#value-identity--how-duplicates-are-decided). Two equal-but-distinct instances are two different keys.

```php
$metadata = ObjectMap::of(User::class, AuditEntry::class);
$metadata->set($alice, $aliceAudit);
$metadata->set($alice, $updatedAudit);   // overwrites — same instance, same key

$metadata->get($alice);       // $updatedAudit
$metadata->get($stranger);    // null — different instance, even if structurally identical to $alice
$metadata->has($bob);         // false
$metadata->remove($alice);    // true
$metadata->remove($alice);    // false — nothing left to remove
```

Object keys aren't expressible via `$map[$obj]` — `ObjectMap` deliberately does **not** implement `ArrayAccess` (PHP offsets are limited to `int|string`). Use `set()` / `get()` instead.

[↑ Back to top](#objectmap)

---

## keys / values

Plain lists, insertion order, index-aligned with each other.

```php
$metadata->keys();     // [$alice, $bob]
$metadata->values();   // [$aliceAudit, $bobAudit]
```

[↑ Back to top](#objectmap)

---

## Iteration and toArray

`foreach` yields `object key => object value` in insertion order:

```php
foreach ($metadata as $user => $entry) {
    echo "{$user->name}: {$entry->summary}\n";
}
```

`toArray()` returns a `list<array{T_Key, T_Value}>` of pairs — the same shape the constructor accepts — because object keys can't be represented as array keys:

```php
$metadata->toArray();   // [[$alice, $aliceAudit], [$bob, $bobAudit]]
```

`count()`, `isEmpty()`, and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md), though `ObjectMap` implements them itself over its two parallel hash-keyed arrays rather than inheriting them.

[↑ Back to top](#objectmap)
