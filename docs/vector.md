# Vector

[← Reference](README.md)

Int-indexed dynamic array of typed (or mixed) values, with `ArrayAccess` on top of the shared collection base.

```php
use Rak200\Collections\Vector;
```

Reach for it when you need an ordered, int-indexed list with type enforcement: DTO collections, search results, paginated rows.

## Contents

- [Construction — any / of / ofInt / ofString / ofBool / ofFloat / ofObject / ofCallable](#construction)
- [add / get / remove](#add--get--remove)
- [ArrayAccess — `$v[$i]`, `$v[] =`, `isset`, `unset`](#arrayaccess)
- [Inherited — getType / count / isEmpty / clear / toArray / iteration](#inherited)

---

## Construction

Constructors are `protected`; build through the factories. Every factory takes the initial items as its last argument.

```php
$ints  = Vector::ofInt([1, 2, 3]);       // Vector<int>
$users = Vector::of(User::class, [$a]);  // Vector<User>
$bag   = Vector::any();                  // Vector<mixed> — no enforcement

Vector::ofString();
Vector::ofBool();
Vector::ofFloat();
Vector::ofObject();
Vector::ofCallable();
```

Initial items are re-indexed from `0` in iteration order. Anything failing the type check raises `InvalidArgumentException` — see [type discriminators](internals.md#type-discriminators).

```php
Vector::ofInt(['three']);   // InvalidArgumentException: Item must be an instance of int. Got: string
```

[↑ Back to top](#vector)

---

## add / get / remove

`add()` writes at an explicit offset, `get()` reads one, `remove()` deletes one. All three are O(1), and `remove()` leaves a hole rather than re-indexing.

```php
$users = Vector::of(User::class);
$users->add(0, $alice);
$users->add(1, $bob);

$users->get(0);      // $alice
$users->get(99);     // null — missing offsets read as null, they do not throw

$users->remove(0);
$users->count();     // 1
$users->toArray();   // [1 => $bob] — offset 1 kept, not renumbered
```

`add()` validates the value against the vector's type and raises `InvalidArgumentException` on a mismatch. Because `remove()` does not renumber, `toArray()` on a vector with holes is an associative array, not a list — call `array_values()` yourself if you need a packed list.

[↑ Back to top](#vector)

---

## ArrayAccess

The full `ArrayAccess` surface is implemented, so a `Vector` reads and writes like a native array. Appending with `$v[] =` picks the next free integer offset.

```php
$bag = Vector::any();
$bag[] = 42;
$bag[] = 'hello';

$bag[0];            // 42
isset($bag[1]);     // true
isset($bag[9]);     // false
unset($bag[0]);
count($bag);        // 1
```

Writes go through the same type check as `add()`:

```php
$counts = Vector::ofInt();
$counts[] = 42;        // ok
$counts[] = 'three';   // InvalidArgumentException
```

Offsets must be integers; `$v['key']` is not a supported shape — use [`Map`](map.md) for string keys.

[↑ Back to top](#vector)

---

## Inherited

`getType()`, `count()`, `isEmpty()`, `clear()`, `toArray()`, and the `Iterator` methods come from [`AbstractCollection`](abstract-collection.md) unchanged. `toArray()` returns the underlying array with its keys intact, and `key()` is nullable past the end.

```php
$v = Vector::ofString(['a', 'b']);

foreach ($v as $i => $value) {
    echo "$i => $value\n";   // 0 => a, then 1 => b
}

$v->getType();   // 'string'
$v->toArray();   // ['a', 'b']
```

[↑ Back to top](#vector)
