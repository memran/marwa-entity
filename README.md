# 🧩 Marwa\Entity

**Define once. Protect everywhere.**

A lightweight, framework-agnostic **entity schema and validation library** for PHP 8.2+  
used across the Marwa ecosystem — powering request validation, form building, and database migrations.

---

## 🚀 Overview

`Marwa\Entity` lets you define your application data structure **once** and reuse it safely across:

- ✅ **Validation** (for HTTP requests, CLI inputs, API payloads, etc.)
- 🧱 **Entity schema definition** (with metadata, UI hints, and type safety)
- 🧾 **Migration builders** (generate portable table specs from your schema)
- 🧰 **Form builders** (for Twig, Blade, or any UI layer)
- 🔄 **Future support:** load schemas from **YAML / JSON** definition files.

It’s part of the [Marwa Framework](https://github.com/memran) ecosystem, but fully standalone and PSR-compatible.

---

## 🧠 Philosophy

> **Define once, use everywhere.**

Instead of repeating validation logic across models, forms, and migrations,  
you define your entity schema once and reuse it safely in:

| Layer               | Uses                                                |
| ------------------- | --------------------------------------------------- |
| **marwa/request**   | Validates PSR-7 requests with the same schema       |
| **marwa/view**      | Builds Twig forms from `uiSpec()`                   |
| **marwa/migration** | Generates database migrations via `migrationSpec()` |

---

## 📦 Installation

```bash
composer require memran/marwa-entity
```

### Requirements:

      * PHP 8.2 or higher
      * PSR-4 autoloading enabled (Composer handles this)

# Basic Exam

```bash
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Validation\Validator;
use Marwa\Entity\Validation\Rules\{Required, Min};
use Marwa\Entity\Support\Sanitizers;

// 1️⃣ Define your entity schema
$schema = EntitySchema::make('users');

$schema->string('name')
    ->label('Full Name')
    ->rule(new Required(), new Min(3))
    ->sanitize(Sanitizers::trim());

$schema->string('email')
    ->label('Email Address')
    ->rule(new Required())
    ->sanitize(Sanitizers::trim(), Sanitizers::lower())
    ->meta('unique', true)
    ->meta('widget', 'email');

$schema->boolean('is_active')
    ->label('Active?')
    ->meta('default', true);

// 2️⃣ Validate & sanitize input data
$validator = new Validator();
$entity    = new Entity($schema, $validator);

$input = [
    'name' => '  Emran  ',
    'email' => '  TEST@EXAMPLE.com ',
    'is_active' => '1',
];

try {
    $validated = $entity->hydrate($input);
    print_r($validated);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

### Output

```bash
Array
(
    [name] => Emran
    [email] => test@example.com
    [is_active] => 1
)
```

### Core Concepts

| Concept       |        Class        |                                                  Purpose |
| :------------ | :-----------------: | -------------------------------------------------------: |
| Entity Schema |    EntitySchema     |                                  Blueprint of all fields |
| Field         |        Field        | Single field definition (type, label, rules, sanitizers) |
| Validator     |      Validator      |             Evaluates schema rules and builds error bags |
| Rules         |       Rules\*       | Pluggable validation rules (Required, Min, Unique, etc.) |
| ErrorBag      | Validation\ErrorBag |                             Collects validation messages |
| Sanitizers    | Support\Sanitizers  |                                  Built-in input cleaners |
| Entity        |       Entity        |           Executes validation, sanitization, and casting |

### Rule System

Add rules fluently:

```bash
$schema->string('password')
->rule(new Required(), new Min(8));
```

### Built-in rules:

| Rule           |                   Purpose                   |
| :------------- | :-----------------------------------------: |
| Required       |            Value must be present            |
| StringRule     |              Must be a string               |
| IntegerRule    |             Must be an integer              |
| Min,Max        |       Numeric or string length checks       |
| Email, Regex   |                Format checks                |
| InArray        |     Must match one of the given values      |
| Unique, Exists | Custom callable checks (framework-agnostic) |

✅ Unique and Exists are callback-based — you pass your own closure to query DB or API.

### Sanitizers

Sanitizers are lightweight closures applied before validation.

```bash
use Marwa\Entity\Support\Sanitizers;

$schema->string('username')
->sanitize(Sanitizers::trim(), Sanitizers::lower());
```

Built-in:
trim()
lower()
stripTags(array $allowed = [])

You can define your own:

```bash
$schema->string('slug')->sanitize(fn($v)=>str_replace(' ','-',strtolower($v)))
```

### Example: Migration Spec

```bash
print_r($schema->migrationSpec());
```

Output:

```bash
[
  'name' => [
    'type' => 'string',
    'enum' => null,
    'nullable' => false,
    'index' => false,
    'unique' => false,
    'default' => null,
    'precision' => null,
    'scale' => null
  ],
  'email' => [
    'type' => 'string',
    'enum' => null,
    'nullable' => false,
    'index' => false,
    'unique' => true,
    'default' => null,
    'precision' => null,
    'scale' => null
  ]
]
```

## Integration Examples

✅ In a PSR-7 Request Library

```bash
// marwa/request
$form = new UserStoreRequest($request, $userEntity);
$data = $form->validated(); // uses Marwa\Entity internally
```

🧱 In a Migration Library

```bash
$table->applyEntitySpec($schema->migrationSpec());
```

🎨 In a View Library

```bash
$fields = $schema->uiSpec(); // used by Twig macro to build form
```

🔮 Roadmap

     * JSON / YAML schema loading (auto via SchemaFactory)
     * Rule registration via container or config
     * Localization & message translation
     * Typed casting customization
     * Nested entity relationships
     * Advanced schema introspection

⚖️ License

Released under the MIT License.
© 2025 Mohammad Emran

🌟 Acknowledgments

Built with ❤️ by the Marwa Open Source Team
for developers who love clarity, reusability, and DX-first design.
