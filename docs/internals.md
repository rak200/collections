# Internals — typing, identity, factories

[← Reference](README.md)

The three helpers under `Rak200\Collections\Internal` that every collection is built on: how a type discriminator is validated, how values get a uniqueness handle, and where the static factories come from. They are `@internal` — not part of the public contract — but the behaviour they define is observable through every public API, so it is documented once here and referenced from each class page.

```php
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;
```

## Contents

- [Type discriminators](#type-discriminators)
- [Construction — the factory scheme](#construction--the-factory-scheme)
- [Value identity — how duplicates are decided](#value-identity--how-duplicates-are-decided)

---

## Type discriminators

Every collection stores one (or two) **discriminator strings** and validates each incoming value against it through `ValidatesType::checkType()`. The accepted strings are:

| Discriminator | Accepts |
| ------------- | ------- |
| `'mixed'` | anything — the check is skipped entirely |
| `'object'` | any object |
| `'int'` / `'integer'` | `int` |
| `'string'` | `string` |
| `'bool'` / `'boolean'` | `bool` |
| `'float'` / `'double'` | `float` |
| `'array'` | `array` |
| `'iterable'` | `array` or `Traversable` |
| `'callable'` | anything `is_callable()` accepts |
| any other string | treated as a class-string and checked with `is_a()` |

A value that fails the check raises `InvalidArgumentException`. The message carries a label so key and value rejections read differently:

```php
ValidatesType::checkType('int', 42);           // ok — returns void
ValidatesType::checkType('int', 'nope');       // InvalidArgumentException: Item must be an instance of int. Got: string
ValidatesType::checkType('string', 42, 'Key'); // InvalidArgumentException: Key must be an instance of string. Got: int
ValidatesType::checkType(DateTimeImmutable::class, new DateTimeImmutable()); // ok
```

Two narrower constraints apply on top of the table:

- **Map keys** (`Map`, `BiMap`, `MultiMap`, `ImmutableMap`) accept only `'int'`, `'string'`, or `'mixed'` — PHP array keys can only be `int|string`.
- **`ObjectMap`** accepts only `'object'` or a class-string, on both the key and the value side.

`ValidatesType` is an abstract class with a single static method, not a trait: it carries no state and needs no `$type` property in the using class, so any collection can call it regardless of its storage model.

[↑ Back to top](#internals--typing-identity-factories)

---

## Construction — the factory scheme

Constructors are `protected`. A discriminator passed to `new` is just a runtime string, and neither PHPStan nor the IDE can turn it into a generic parameter; a factory with a literal `@return self<int>` is understood by both. So every collection is built through static factories instead:

| Factory | Element type |
| ------- | ------------ |
| `X::any(…)` | `mixed` — no runtime enforcement |
| `X::of(Foo::class, …)` | instances of `Foo` |
| `X::ofInt()` / `ofString()` / `ofBool()` / `ofFloat()` / `ofObject()` / `ofCallable()` | the matching PHP built-in |

```php
$ints  = Vector::ofInt();              // Vector<int>
$users = Vector::of(User::class);      // Vector<User>
$bag   = Vector::any();                // Vector<mixed>
```

The pseudo-type factories come from the `ProvidesValueFactories` trait and exist on the ten single-value collections: `Vector`, `Set`, `Stack`, `OrderedSet`, `MultiSet`, `PriorityQueue`, `ImmutableSet`, `LinkedList`, `Queue`, `Deque`.

`of()` is deliberately **not** in the trait — binding a per-call method template (`class-string<T>` → `self<T>`) does not resolve through a trait in IDE analysis — so each class declares its own `of()` inline. That is also why the signatures differ where the class needs it: `CircularBuffer::of($capacity, Foo::class, …)`, `OrderedSet::of(Foo::class, $items, ?$comparator)`, `Map::of($keyType, Foo::class, …)`, `ObjectMap::of(Key::class, Value::class, …)`.

Key/value collections have no per-scalar factory for the **value** side — use `any()` when the values are scalars, or a class-string with `of()`.

The `phpstan/CollectionTypeResolver.php` extension shipped with the package closes the remaining gap for direct constructor calls, binding the generics from the discriminator argument. It is registered in `phpstan.neon.dist`.

[↑ Back to top](#internals--typing-identity-factories)

---

## Value identity — how duplicates are decided

`Set`, `OrderedSet`, `MultiSet`, `ImmutableSet`, `BiMap` (value side), and `ObjectMap` (key side) all need a string handle for an arbitrary value. `HashesValues::hashValue()` supplies it with a **hybrid** scheme:

| Value | Handle |
| ----- | ------ |
| object | `spl_object_id()` — identity, so equal-but-distinct instances are **different** entries |
| scalar / `null` | the value itself, type-prefixed |
| array | `Hash::md5(serialize($value))` — structural equality |

Every handle carries a type prefix (`o:`, `i:`, `s:`, …), so values of different types never collide:

```php
$s = Set::any();
$s->add(1);      // true
$s->add('1');    // true  — int 1 and string '1' are distinct entries
$s->count();     // 2

$a = new stdClass();
$b = new stdClass();  // equal contents, different instance
$s->add($a);     // true
$s->add($a);     // false — same instance
$s->add($b);     // true  — identity, not equality
```

The object rule is the one that surprises: two value objects that compare `==` are still two separate members. When you want equality semantics, key on a scalar you derive yourself (an id, a hash) rather than on the object.

A scalar handle carries the value verbatim after its prefix (`'s:a.b'` for the string `'a.b'`), and every lookup against it is a literal-key one — so a string containing a dot behaves like any other member:

```php
$s = Set::any(['a.b', 'a.c', 'a']);
$s->contains('a.b');  // true
$s->add('a.b');       // false — already a member
$s->count();          // 3
```

[↑ Back to top](#internals--typing-identity-factories)
