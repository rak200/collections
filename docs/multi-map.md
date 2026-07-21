# MultiMap

[← Reference](README.md)

Key-to-many-values map: each key holds an ordered list of values, and the same key can be added to repeatedly.

```php
use Rak200\Collections\MultiMap;
```

Reach for it for HTTP headers (where keys can legitimately repeat), `groupBy` results, or a tag → entity index — anywhere one key naturally owns many values.

## Contents

- [Construction — any / of](#construction)
- [getKeyType / getValueType](#getkeytype--getvaluetype)
- [add / set](#add--set)
- [get / getFirst / has / hasValue](#get--getfirst--has--hasvalue)
- [remove / removeValue](#remove--removevalue)
- [keys / values](#keys--values)
- [countKey / count / total](#countkey--count--total)
- [Iteration and toArray](#iteration-and-toarray)

---

## Construction

Key discriminator first, value discriminator second — same order as [`Map`](map.md). Neither factory takes initial entries; build the map, then call `add()` / `set()`.

```php
$headers = MultiMap::of('string', 'string');   // MultiMap<string, string>
$any     = MultiMap::any();                    // MultiMap<mixed, mixed> — untyped
```

Both discriminators support the full set described in [type discriminators](internals.md#type-discriminators) (the key side still narrowed to `'int'`/`'string'`/`'mixed'`, same as `Map`).

[↑ Back to top](#multimap)

---

## getKeyType / getValueType

```php
$headers = MultiMap::of('string', 'string');
$headers->getKeyType();     // 'string'
$headers->getValueType();   // 'string'
```

[↑ Back to top](#multimap)

---

## add / set

`add()` appends one value under a key, creating the entry if absent — the everyday mutator. `set()` replaces a key's **entire** value list in one call.

```php
$headers = MultiMap::any();
$headers->add('Set-Cookie', 'session=abc');
$headers->add('Set-Cookie', 'tracking=xyz');
$headers->get('Set-Cookie');     // ['session=abc', 'tracking=xyz']

$headers->set('Set-Cookie', ['fresh=1']);   // discards the previous two values
$headers->get('Set-Cookie');                // ['fresh=1']
```

Both validate the key; `set()` additionally validates **every** value in the list before writing any of it, so a single bad value rejects the whole call. Violations raise `InvalidArgumentException` labelled `Key` or `Value`.

[↑ Back to top](#multimap)

---

## get / getFirst / has / hasValue

`get()` is always safe — a missing key reads as an empty list, never `null`. `getFirst()` is the shortcut for "just the first value, or null". `hasValue()` does a linear scan of one key's list (strict comparison).

```php
$headers->get('Missing');                    // []
$headers->getFirst('Content-Type');          // 'text/html', or null if absent
$headers->has('Set-Cookie');                 // true
$headers->hasValue('Set-Cookie', 'fresh=1'); // true
$headers->hasValue('Set-Cookie', 'nope');    // false
```

[↑ Back to top](#multimap)

---

## remove / removeValue

`remove()` drops a key and every value under it in one O(1) call. `removeValue()` drops a single occurrence — the **first** match — and drops the key entirely if that was its last value.

```php
$headers->remove('Set-Cookie');       // true — key and all its values gone
$headers->remove('Set-Cookie');       // false — already gone

$m = MultiMap::any();
$m->add('k', 'a');
$m->add('k', 'a');
$m->removeValue('k', 'a');            // true — removes one 'a', the other stays
$m->get('k');                         // ['a']

$m->removeValue('k', 'a');
$m->has('k');                         // false — last value removed, key dropped too
```

[↑ Back to top](#multimap)

---

## keys / values

`keys()` lists each distinct key once, insertion order. `values()` is the **flattened** list of every value across every key — not index-aligned with `keys()`.

```php
$m = MultiMap::any();
$m->add('a', 1);
$m->add('b', 2);
$m->add('a', 3);

$m->keys();     // ['a', 'b']
$m->values();   // [1, 3, 2] — flattened in insertion order, not grouped
```

[↑ Back to top](#multimap)

---

## countKey / count / total

Three different questions that are easy to conflate. `countKey()` is how many values one key has. `count()` — the `Countable` implementation — is the number of **distinct keys**. `total()` is the number of values across **all** keys.

```php
$m = MultiMap::any();
$m->add('a', 1);
$m->add('a', 2);
$m->add('b', 3);

$m->countKey('a');   // 2
$m->countKey('c');   // 0 — absent key, not an error
$m->count();         // 2 — distinct keys: 'a', 'b'
count($m);           // 2 — Countable agrees
$m->total();          // 3 — total values across both keys
```

[↑ Back to top](#multimap)

---

## Iteration and toArray

Iteration yields **one entry per stored value** — a key with three values appears three times — which is the natural shape for "process every (key, value) pair" consumers:

```php
$m = MultiMap::any();
$m->add('a', 1);
$m->add('a', 2);
$m->add('b', 3);

foreach ($m as $key => $value) {
    // ('a', 1), ('a', 2), ('b', 3)
}
```

The flattened snapshot is built lazily on `rewind()` and held on the instance, so **nested `foreach` loops over the same map interfere with each other** — iterate a copy or `toArray()` for a concurrent pass.

`toArray()` instead preserves the **grouped** shape (`array<T_Key, list<T_Value>>`), matching what `set()` and the constructor-style factories expect:

```php
$m->toArray();   // ['a' => [1, 2], 'b' => [3]]
```

`isEmpty()` and `clear()` behave as described in [`AbstractCollection`](abstract-collection.md), though `MultiMap` implements them itself over its own storage.

[↑ Back to top](#multimap)
