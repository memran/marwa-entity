<?php

declare(strict_types=1);

/**
 * Marwa Entity - Comprehensive Examples
 *
 * This file demonstrates:
 * 1. Loading schema from JSON
 * 2. Loading schema from Array
 * 3. Loading schema from YAML
 * 4. Custom sanitizer registration
 * 5. Custom validation rule usage
 * 6. Error handling and formatting
 */

require __DIR__ . '/../vendor/autoload.php';

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Entity\SchemaFactory;
use Marwa\Entity\Support\SanitizerFactory;
use Marwa\Entity\Validation\Validator;
use Marwa\Support\Json;
use Marwa\Support\Validation\Contracts\RuleInterface;

// ============================================================================
// EXAMPLE 1: Load Schema from JSON
// ============================================================================

echo "=== Example 1: JSON Schema ===\n";

$jsonSchema = Json::encode([
    'name' => 'users',
    'fields' => [
        'name' => [
            'type' => 'string',
            'label' => 'Full Name',
            'sanitize' => ['trim'],
        ],
        'email' => [
            'type' => 'string',
            'label' => 'Email Address',
            'sanitize' => ['trim', 'lower'],
        ],
        'age' => [
            'type' => 'integer',
            'label' => 'Age',
        ],
    ],
]);

$schemaFromJson = SchemaFactory::fromJson(
    $jsonSchema,
    /** @return RuleInterface */
    static fn(string $name, array $params): RuleInterface => throw new \RuntimeException('Rules not shown in this example'),
    /** @return callable(mixed): mixed */
    static fn(string $name, array $params): callable => SanitizerFactory::make($name, $params),
);

echo "Loaded from JSON: " . ($schemaFromJson->getName() ?? 'unnamed') . "\n";
echo "Fields: " . implode(', ', array_keys($schemaFromJson->fields())) . "\n\n";

// ============================================================================
// EXAMPLE 2: Load Schema from Array
// ============================================================================

echo "=== Example 2: Array Schema ===\n";

$arraySchema = [
    'name' => 'products',
    'fields' => [
        'title' => [
            'type' => 'string',
            'label' => 'Product Title',
            'sanitize' => ['trim'],
        ],
        'price' => [
            'type' => 'decimal',
            'label' => 'Price',
        ],
        'in_stock' => [
            'type' => 'boolean',
            'label' => 'In Stock',
        ],
    ],
];

$schemaFromArray = SchemaFactory::fromArray(
    $arraySchema,
    static fn(string $name, array $params): RuleInterface => throw new \RuntimeException('Rules not shown in this example'),
    static fn(string $name, array $params): callable => SanitizerFactory::make($name, $params),
);

echo "Loaded from Array: " . ($schemaFromArray->getName() ?? 'unnamed') . "\n";
echo "Fields: " . implode(', ', array_keys($schemaFromArray->fields())) . "\n\n";

// ============================================================================
// EXAMPLE 3: Load Schema from YAML
// ============================================================================

echo "=== Example 3: YAML Schema ===\n";

$yamlSchema = <<<'YAML'
name: posts
fields:
  title:
    type: string
    label: Post Title
    sanitize:
      - trim
  content:
    type: string
    label: Content
    sanitize:
      - strip_tags
  published:
    type: boolean
    label: Published
YAML;

// Note: YAML decoder must be provided (e.g., symfony/yaml or spyc)
$yamlDecoder = static function (string $yaml): array {
    // Simple YAML parser for demonstration
    // In production, use: Symfony\Component\Yaml\Yaml::parse($yaml)
    throw new \RuntimeException('YAML decoder not implemented - install symfony/yaml');
};

try {
    $schemaFromYaml = SchemaFactory::fromYaml(
        $yamlSchema,
        $yamlDecoder,
        static fn(string $name, array $params): RuleInterface => throw new \RuntimeException('Rules not shown in this example'),
        static fn(string $name, array $params): callable => SanitizerFactory::make($name, $params),
    );
    echo "Loaded from YAML: " . $schemaFromYaml->name() . "\n";
    echo "Fields: " . implode(', ', array_keys($schemaFromYaml->fields())) . "\n\n";
} catch (\RuntimeException $e) {
    echo "YAML example requires symfony/yaml package\n";
    echo "Install with: composer require symfony/yaml\n\n";
}

// ============================================================================
// EXAMPLE 4: Custom Sanitizer
// ============================================================================

echo "=== Example 4: Custom Sanitizer ===\n";

// Register a custom sanitizer
SanitizerFactory::register('slug', static function (array $params): callable {
    return static function (mixed $value): string {
        if (!is_string($value)) {
            return '';
        }

        // Convert to lowercase, replace spaces with hyphens
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    };
});

$schema = EntitySchema::make('posts');
$schema->string('title')
    ->label('Post Title')
    ->sanitize(SanitizerFactory::make('slug'));

$entity = new Entity($schema, new Validator());

$data = $entity->hydrate(['title' => 'Hello World!']);

echo "Custom sanitizer result: '" . $data['title'] . "'\n";
echo "Expected: 'hello-world'\n\n";

// ============================================================================
// EXAMPLE 5: Custom Validation Rule
// ============================================================================

echo "=== Example 5: Custom Validation Rule ===\n";

/**
 * Custom rule: Must be a valid username
 * - 3-20 characters
 * - Alphanumeric and underscores only
 */
class UsernameRule implements RuleInterface
{
    public function __construct() {}

    public function name(): string
    {
        return 'username';
    }

    public function validate(mixed $value, array $context): bool
    {
        if ($value === null || $value === '') {
            return true; // Use required rule for mandatory fields
        }

        if (!is_string($value)) {
            return false;
        }

        // Must be 3-20 chars, alphanumeric and underscores only
        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,19}$/', $value) === 1;
    }

    public function message(string $field, array $attributes): string
    {
        return "The {$field} must be 3-20 characters and contain only letters, numbers, and underscores.";
    }

    public function params(): array
    {
        return [];
    }
}

// Create schema with custom rule
$schema = EntitySchema::make('users');
$schema->string('username')
    ->label('Username')
    ->rule(new UsernameRule());

$entity = new Entity($schema, new Validator());

// Test valid input
try {
    $data = $entity->hydrate(['username' => 'john_doe']);
    echo "Valid username: " . $data['username'] . "\n";
} catch (\InvalidArgumentException $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}

// Test invalid input
try {
    $data = $entity->hydrate(['username' => 'ab']); // Too short
    echo "Unexpected success\n";
} catch (\InvalidArgumentException $e) {
    $errors = Json::decode($e->getMessage());
    echo "Invalid username error: " . $errors['username'][0] . "\n";
}

try {
    $data = $entity->hydrate(['username' => '123invalid']); // Starts with number
    echo "Unexpected success\n";
} catch (\InvalidArgumentException $e) {
    $errors = Json::decode($e->getMessage());
    echo "Invalid username error: " . $errors['username'][0] . "\n";
}

echo "\n";

// ============================================================================
// EXAMPLE 6: Error Handling and Formatting
// ============================================================================

echo "=== Example 6: Error Handling and Formatting ===\n";

$schema = EntitySchema::make('users');
$schema->string('name')->label('Name');
$schema->string('email')->label('Email');
$schema->integer('age')->label('Age');
$schema->json('preferences')->label('Preferences');

$entity = new Entity($schema, new Validator());

// Test with multiple validation errors
$invalidData = [
    'name' => '',           // Empty (would need required rule)
    'email' => 'invalid',   // Invalid email format (no email rule in demo)
    'age' => 'not-a-number', // Invalid integer
    'preferences' => 'not json', // Invalid JSON
];

try {
    $entity->hydrate($invalidData);
} catch (\InvalidArgumentException $e) {
    $errors = Json::decode($e->getMessage());

    echo "Validation Errors:\n";
    echo str_repeat('-', 50) . "\n";

    /** @var array<string, array<string, string>> $errors */
    foreach ($errors as $field => $messages) {
        foreach ($messages as $message) {
            echo "• {$field}: {$message}\n";
        }
    }

    echo str_repeat('-', 50) . "\n";

    // Alternative: Use Validator directly for more control
    $validator = new Validator();
    $errorBag = $validator->validate($schema, $invalidData);

    echo "\nUsing ErrorBag directly:\n";
    echo "Has errors: " . ($errorBag->hasAny() ? 'Yes' : 'No') . "\n";

    if ($errorBag->hasAny()) {
        echo "All errors:\n";
        foreach ($errorBag->all() as $field => $messages) {
            echo "  {$field}: " . implode(', ', $messages) . "\n";
        }

        echo "\nFirst error for 'email': " . ($errorBag->first('email') ?? 'none') . "\n";

        echo "\nFirst of all fields:\n";
        foreach ($errorBag->firstOfAll() as $field => $message) {
            echo "  {$field}: {$message}\n";
        }
    }
}

echo "\n";

// ============================================================================
// EXAMPLE 7: Full Working Example with Rules
// ============================================================================

echo "=== Example 7: Complete Example with Rules ===\n";

use Marwa\Support\Validation\Rules\EmailRule;
use Marwa\Support\Validation\Rules\MinRule;
use Marwa\Support\Validation\Rules\RequiredRule;

// Create a complete schema with validation rules
$schema = EntitySchema::make('users');
$schema->string('name')
    ->label('Full Name')
    ->rule(new RequiredRule())
    ->rule(new MinRule('3'))
    ->sanitize(SanitizerFactory::make('trim'));

$schema->string('email')
    ->label('Email Address')
    ->rule(new RequiredRule())
    ->rule(new EmailRule())
    ->sanitize(SanitizerFactory::make('trim'), SanitizerFactory::make('lower'));

$schema->integer('age')
    ->label('Age')
    ->rule(new MinRule('18'));

$entity = new Entity($schema, new Validator());

echo "Valid input test:\n";
try {
    $data = $entity->hydrate([
        'name' => '  John Doe  ',
        'email' => '  JOHN@EXAMPLE.COM  ',
        'age' => '25',
    ]);
    echo "Success! Hydrated data:\n";
    print_r($data);
} catch (\InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nInvalid input test:\n";
try {
    $data = $entity->hydrate([
        'name' => 'AB',       // Too short (min: 3)
        'email' => 'invalid', // Invalid email
        'age' => '15',        // Under 18
    ]);
    echo "Unexpected success\n";
} catch (\InvalidArgumentException $e) {
    $errors = Json::decode($e->getMessage());

    echo "Validation failed with " . count($errors) . " error(s):\n";
    foreach ($errors as $field => $messages) {
        foreach ($messages as $message) {
            echo "  • {$field}: {$message}\n";
        }
    }
}

echo "\n=== All Examples Complete ===\n";
